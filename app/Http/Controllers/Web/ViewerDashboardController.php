<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiDecision;
use App\Models\Device;
use App\Models\DryingBatch;
use App\Models\Notification;
use App\Models\SensorReading;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ViewerDashboardController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function dashboard(): View
    {
        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        $sensor = $device
            ? SensorReading::valid()->forDevice($device->id)->latest('recorded_at')->first()
            : SensorReading::valid()->latest('recorded_at')->first();

        $activeBatch = $device
            ? DryingBatch::active()->where('device_id', $device->id)->latest()->first()
            : DryingBatch::active()->latest()->first();

        $latestDecision = $device
            ? AiDecision::where('device_id', $device->id)->latest('decided_at')->first()
            : AiDecision::latest('decided_at')->first();

        $moistureProgress = null;
        if ($activeBatch && $activeBatch->initial_moisture > $activeBatch->target_moisture) {
            $reduced = $activeBatch->initial_moisture - ($activeBatch->current_moisture ?? $activeBatch->initial_moisture);
            $total   = $activeBatch->initial_moisture - $activeBatch->target_moisture;
            $moistureProgress = min(100, max(0, round($reduced / $total * 100)));
        }

        // ── Estimasi waktu pengeringan ─────────────────────────────────────
        // Hitung laju penurunan moisture (% per jam) dari 5 reading terakhir
        // dalam 2 jam terakhir. Jika data cukup, estimasi jam tersisa.
        $dryingEstimation = $this->estimateDryingTime($activeBatch, $device);

        return view('viewer.dashboard', compact(
            'device', 'sensor', 'activeBatch', 'latestDecision',
            'moistureProgress', 'dryingEstimation'
        ));
    }

    /**
     * Estimasi sisa waktu pengeringan berdasarkan laju penurunan moisture.
     *
     * @return array{
     *   available: bool,
     *   rate_per_hour: float|null,       // %/jam
     *   remaining_moisture: float|null,   // % yang harus turun lagi
     *   estimated_hours: float|null,      // jam tersisa
     *   estimated_finish: string|null,    // waktu selesai format H:i
     *   confidence: string,               // 'high'|'medium'|'low'|'none'
     *   message: string,                  // pesan untuk petani
     * }
     */
    private function estimateDryingTime(?DryingBatch $batch, $device): array
    {
        $none = [
            'available'          => false,
            'rate_per_hour'      => null,
            'remaining_moisture' => null,
            'estimated_hours'    => null,
            'estimated_finish'   => null,
            'confidence'         => 'none',
            'message'            => 'Tidak ada proses pengeringan aktif.',
        ];

        if (!$batch || $batch->status !== 'drying') {
            if ($batch && $batch->status === 'paused') {
                return array_merge($none, ['message' => 'Pengeringan dijeda. Estimasi tidak tersedia.']);
            }
            return $none;
        }

        $currentMoisture = (float) ($batch->current_moisture ?? $batch->initial_moisture);
        $targetMoisture  = (float) $batch->target_moisture;
        $remaining       = $currentMoisture - $targetMoisture;

        if ($remaining <= 0) {
            return array_merge($none, [
                'available'          => true,
                'remaining_moisture' => 0,
                'estimated_hours'    => 0,
                'confidence'         => 'high',
                'message'            => '✅ Gabah sudah mencapai target kadar air!',
            ]);
        }

        // Ambil 8 reading valid terakhir dalam 3 jam, prioritaskan batch ini
        $readingsQuery = SensorReading::valid()
            ->where('recorded_at', '>=', now()->subHours(3))
            ->whereNotNull('grain_moisture')
            ->orderByDesc('recorded_at')
            ->limit(8);

        if ($batch->id) {
            $readingsQuery->where('batch_id', $batch->id);
        } elseif ($device) {
            $readingsQuery->where('device_id', $device->id);
        }

        $readings = $readingsQuery->get(['grain_moisture', 'recorded_at']);

        // Fallback: pakai selisih current vs initial moisture & waktu batch berjalan
        if ($readings->count() < 2) {
            $durationHours = $batch->durationMinutes()
                ? $batch->durationMinutes() / 60
                : null;

            $moistureDropped = (float) $batch->initial_moisture - $currentMoisture;

            if ($durationHours && $durationHours > 0.1 && $moistureDropped > 0) {
                $rate          = $moistureDropped / $durationHours;
                $estimatedHours = $remaining / $rate;
                $confidence    = $durationHours >= 1 ? 'medium' : 'low';

                return [
                    'available'          => true,
                    'rate_per_hour'      => round($rate, 2),
                    'remaining_moisture' => round($remaining, 1),
                    'estimated_hours'    => round($estimatedHours, 1),
                    'estimated_finish'   => now()->addHours($estimatedHours)->format('H:i'),
                    'confidence'         => $confidence,
                    'message'            => $this->buildEstimationMessage($estimatedHours, $confidence),
                ];
            }

            return array_merge($none, [
                'available' => true,
                'remaining_moisture' => round($remaining, 1),
                'message'   => 'Data sensor belum cukup untuk estimasi akurat.',
            ]);
        }

        // Hitung linear regression sederhana: slope moisture terhadap waktu
        $points = $readings->map(function ($r) use ($batch) {
            return [
                't' => $batch->start_time
                    ? $batch->start_time->diffInMinutes($r->recorded_at) / 60 // jam sejak mulai
                    : 0,
                'm' => (float) $r->grain_moisture,
            ];
        })->sortBy('t')->values();

        // Rate = (moisture_awal_window - moisture_akhir_window) / selang_waktu
        $oldest = $points->first();
        $newest = $points->last();
        $timeDiff = $newest['t'] - $oldest['t']; // jam

        if ($timeDiff < 0.05) { // kurang dari 3 menit
            return array_merge($none, [
                'available' => true,
                'remaining_moisture' => round($remaining, 1),
                'message'   => 'Sensor baru mulai merekam. Estimasi tersedia dalam beberapa menit.',
            ]);
        }

        $moistureDrop = $oldest['m'] - $newest['m']; // positif = turun
        $rate = $moistureDrop / $timeDiff; // %/jam

        if ($rate <= 0) {
            return [
                'available'          => true,
                'rate_per_hour'      => 0,
                'remaining_moisture' => round($remaining, 1),
                'estimated_hours'    => null,
                'estimated_finish'   => null,
                'confidence'         => 'low',
                'message'            => '⚠️ Kadar air tidak turun. Periksa kondisi mesin atau cuaca.',
            ];
        }

        $estimatedHours = $remaining / $rate;
        $confidence = $readings->count() >= 5 ? 'high' : ($readings->count() >= 3 ? 'medium' : 'low');

        return [
            'available'          => true,
            'rate_per_hour'      => round($rate, 2),
            'remaining_moisture' => round($remaining, 1),
            'estimated_hours'    => round($estimatedHours, 1),
            'estimated_finish'   => now()->addHours($estimatedHours)->format('H:i, d M'),
            'confidence'         => $confidence,
            'message'            => $this->buildEstimationMessage($estimatedHours, $confidence),
        ];
    }

    private function buildEstimationMessage(float $hours, string $confidence): string
    {
        $label = match(true) {
            $hours < 0.5  => 'kurang dari 30 menit',
            $hours < 1    => 'sekitar ' . round($hours * 60) . ' menit',
            $hours < 24   => 'sekitar ' . round($hours, 1) . ' jam',
            default       => 'lebih dari ' . floor($hours / 24) . ' hari',
        };

        return match($confidence) {
            'high'   => "Estimasi selesai: $label.",
            'medium' => "Perkiraan selesai: $label (akurasi sedang).",
            'low'    => "Estimasi kasar: $label (data masih sedikit).",
            default  => "Estimasi: $label.",
        };
    }

    /**
     * Endpoint JSON ringan untuk polling dashboard viewer.
     * Dipanggil setiap 30 detik via fetch — tidak render view.
     */
    public function poll(): \Illuminate\Http\JsonResponse
    {
        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        $sensor = $device
            ? SensorReading::valid()->forDevice($device->id)->latest('recorded_at')->first()
            : SensorReading::valid()->latest('recorded_at')->first();

        $activeBatch = $device
            ? DryingBatch::active()->where('device_id', $device->id)->latest()->first()
            : DryingBatch::active()->latest()->first();

        $latestDecision = $device
            ? AiDecision::where('device_id', $device->id)->latest('decided_at')->first()
            : AiDecision::latest('decided_at')->first();

        // Moisture progress
        $moistureProgress = null;
        if ($activeBatch && $activeBatch->initial_moisture > $activeBatch->target_moisture) {
            $reduced = $activeBatch->initial_moisture - ($activeBatch->current_moisture ?? $activeBatch->initial_moisture);
            $total   = $activeBatch->initial_moisture - $activeBatch->target_moisture;
            $moistureProgress = min(100, max(0, round($reduced / $total * 100)));
        }

        // Estimasi
        $est = $this->estimateDryingTime($activeBatch, $device);

        // Device flags
        $heaterOn = $latestDecision
            && $latestDecision->decision_type === 'start_heater'
            && $latestDecision->execution_status === 'executed';
        $fanOn   = (bool) ($latestDecision?->esp32_command['fan'] ?? false);
        $mixerOn = $activeBatch && $activeBatch->status === 'drying';
        $online  = $device?->status === 'online';

        return response()->json([
            'ts' => now()->format('d M Y, H:i:s'),

            'status' => [
                'state'  => $activeBatch?->status ?? 'idle',
                'text'   => match($activeBatch?->status) {
                    'drying' => 'Sedang Dikeringkan',
                    'paused' => 'Dijeda Otomatis',
                    default  => 'Tidak Ada Proses',
                },
                'sub' => match($activeBatch?->status) {
                    'drying' => 'Mesin aktif — gabah sedang diproses',
                    'paused' => 'Kemungkinan hujan — sistem akan lanjut otomatis',
                    default  => 'Tidak ada pengeringan berlangsung saat ini',
                },
                'batch_code'   => $activeBatch?->batch_code,
                'petani_name'  => $activeBatch?->petani_name,
                'rice_variety' => $activeBatch?->rice_variety ?? $activeBatch?->rice_type,
                'weight'       => $activeBatch?->initial_weight,
            ],

            'moisture' => $activeBatch ? [
                'current'  => (float) ($activeBatch->current_moisture ?? $activeBatch->initial_moisture),
                'initial'  => (float) $activeBatch->initial_moisture,
                'target'   => (float) $activeBatch->target_moisture,
                'dropped'  => round((float)$activeBatch->initial_moisture - (float)($activeBatch->current_moisture ?? $activeBatch->initial_moisture), 1),
                'progress' => $moistureProgress ?? 0,
            ] : null,

            'sensor' => $sensor ? [
                'temp_in'   => $sensor->temperature_inside  !== null ? (float) $sensor->temperature_inside  : null,
                'temp_out'  => $sensor->temperature_outside !== null ? (float) $sensor->temperature_outside : null,
                'rh_in'     => $sensor->humidity_inside     !== null ? (float) $sensor->humidity_inside     : null,
                'rh_out'    => $sensor->humidity_outside    !== null ? (float) $sensor->humidity_outside    : null,
                'recorded'  => $sensor->recorded_at?->format('H:i:s'),
            ] : null,

            'devices' => [
                'heater' => $heaterOn,
                'fan'    => $fanOn,
                'mixer'  => $mixerOn,
                'online' => $online,
            ],

            'estimation' => [
                'available'       => $est['available'],
                'estimated_hours' => $est['estimated_hours'],
                'estimated_finish'=> $est['estimated_finish'],
                'rate_per_hour'   => $est['rate_per_hour'],
                'confidence'      => $est['confidence'],
                'message'         => $est['message'],
            ],

            'unread_count' => Notification::where('user_id', auth()->id())
                ->whereNull('read_at')->count(),
        ]);
    }

    public function batches(): View
    {
        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        $batches = DryingBatch::when($device, fn($q) => $q->where('device_id', $device->id))
            ->latest()
            ->paginate(10);

        return view('viewer.batches', compact('batches', 'device'));
    }

    public function notifications(): View
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('viewer.notifications', compact('notifications'));
    }

    /**
     * Halaman chatbot viewer — petani tanya kondisi gabah dalam bahasa natural.
     * Session chatbot terikat ke user + device aktif.
     */
    public function chat(): View
    {
        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        $sessionId = session('viewer_chat_session_' . auth()->id())
            ?? (string) Str::uuid();

        session(['viewer_chat_session_' . auth()->id() => $sessionId]);

        $history = AiConversation::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['role', 'message', 'created_at']);

        $activeBatch = $device
            ? DryingBatch::active()->where('device_id', $device->id)->latest()->first()
            : null;

        return view('viewer.chat', compact('history', 'sessionId', 'device', 'activeBatch'));
    }

    /**
     * Kirim pesan chatbot viewer.
     * System prompt dikunci: bahasa sederhana, konteks gabah milik petani ini saja.
     */
    public function sendChat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message'    => 'required|string|max:500',
            'session_id' => 'required|string',
        ]);

        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        // Simpan pesan user
        AiConversation::create([
            'user_id'    => auth()->id(),
            'device_id'  => $device?->id,
            'session_id' => $data['session_id'],
            'role'       => 'user',
            'message'    => $data['message'],
        ]);

        try {
            $result = $this->ai->chatViewer(
                $data['message'],
                $data['session_id'],
                $device?->id
            );

            AiConversation::create([
                'user_id'     => auth()->id(),
                'device_id'   => $device?->id,
                'session_id'  => $data['session_id'],
                'role'        => 'assistant',
                'message'     => $result['message'],
                'ai_model'    => $result['model'],
                'tokens_used' => $result['tokens_used'],
            ]);
        } catch (\Throwable $e) {
            AiConversation::create([
                'user_id'    => auth()->id(),
                'session_id' => $data['session_id'],
                'role'       => 'assistant',
                'message'    => 'Maaf, sistem sedang tidak bisa menjawab. Coba lagi sebentar.',
            ]);
        }

        return redirect()->route('viewer.chat');
    }

    // ── Request Pengeringan ──────────────────────────────────────────────────

    /**
     * Form pengajuan request pengeringan oleh petani.
     */
    public function requestForm(): View
    {
        $devices = Device::where('status', 'online')->orWhere('status', 'offline')->get();

        // Cek apakah petani sudah punya request pending
        $pendingRequest = DryingBatch::where('requested_by', auth()->id())
            ->where('request_status', 'pending')
            ->latest()
            ->first();

        return view('viewer.request', compact('devices', 'pendingRequest'));
    }

    /**
     * Simpan request pengeringan dari petani.
     * Membuat DryingBatch dengan status 'waiting' dan request_status 'pending'.
     * Operator akan menerima notifikasi untuk approve/reject.
     */
    public function storeRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rice_variety'     => 'required|string|max:100',
            'initial_weight'   => 'required|numeric|min:1|max:10000',
            'initial_moisture' => 'required|numeric|min:10|max:35',
            'target_moisture'  => 'required|numeric|min:10|max:20',
            'request_notes'    => 'nullable|string|max:500',
        ]);

        // Cek tidak ada request pending yang masih menunggu
        $existing = DryingBatch::where('requested_by', auth()->id())
            ->where('request_status', 'pending')
            ->exists();

        if ($existing) {
            return back()->withErrors(['request' => 'Kamu masih memiliki permintaan pengeringan yang menunggu persetujuan operator.']);
        }

        $device = Device::online()->latest('last_seen')->first()
            ?? Device::latest('last_seen')->first();

        if (!$device) {
            return back()->withErrors(['request' => 'Tidak ada perangkat pengering yang tersedia saat ini. Hubungi operator.']);
        }

        $batch = DryingBatch::create([
            'device_id'        => $device->id,
            'batch_code'       => 'REQ-' . strtoupper(Str::random(6)) . '-' . date('ymd'),
            'rice_type'        => 'Padi',
            'rice_variety'     => $data['rice_variety'],
            'initial_weight'   => $data['initial_weight'],
            'initial_moisture' => $data['initial_moisture'],
            'target_moisture'  => $data['target_moisture'],
            'drying_method'    => 'Hybrid',
            'petani_name'      => auth()->user()->name,
            'petani_phone'     => null,
            'requested_by'     => auth()->id(),
            'request_status'   => 'pending',
            'request_notes'    => $data['request_notes'] ?? null,
            'requested_at'     => now(),
            'status'           => 'waiting',
        ]);

        // Notifikasi ke semua operator dan admin
        $operators = \App\Models\User::whereIn('role', ['operator', 'admin'])->get();
        foreach ($operators as $op) {
            \App\Models\Notification::create([
                'user_id'  => $op->id,
                'batch_id' => $batch->id,
                'type'     => 'info',
                'title'    => '📋 Request Pengeringan Baru',
                'message'  => auth()->user()->name . ' mengajukan request pengeringan gabah ' .
                              $data['rice_variety'] . ' (' . $data['initial_weight'] . ' kg). ' .
                              'Silakan tinjau dan setujui.',
                'data'     => ['batch_id' => $batch->id, 'batch_code' => $batch->batch_code],
            ]);
        }

        return redirect()->route('viewer.dashboard')
            ->with('success', '✅ Permintaan pengeringan berhasil diajukan! Operator akan segera meninjau dan menyetujui.');
    }
}
