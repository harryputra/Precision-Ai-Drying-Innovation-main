<?php

namespace App\Http\Controllers\Web;

use App\Exports\BatchExport;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DryingBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = DryingBatch::with('device')->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('batch_code', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $batches = $query->paginate(15)->withQueryString();

        return view('batches.index', compact('batches'));
    }

    public function create(): View
    {
        $devices = Device::orderBy('device_name')->get(['id', 'device_name']);
        return view('batches.create', compact('devices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_id'        => 'required|exists:devices,id',
            'batch_code'       => 'required|string|max:100|unique:drying_batches,batch_code',
            'rice_type'        => 'required|string|max:100',
            'rice_variety'     => 'nullable|string|max:100',
            'initial_weight'   => 'required|numeric|min:0',
            'initial_moisture' => 'required|numeric|min:0|max:100',
            'target_moisture'  => 'required|numeric|min:0|max:100',
            'drying_method'    => 'required|string|max:100',
            'operator_name'    => 'nullable|string|max:100',
            'status'           => 'required|in:waiting,drying,paused,completed,failed',
            'start_time'       => 'nullable|date',
        ]);

        DryingBatch::create($data);

        return redirect()->route('web.batches.index')->with('success', 'Batch berhasil dibuat.');
    }

    public function show(DryingBatch $dryingBatch): View
    {
        $dryingBatch->load('device');

        $aiDecisions  = $dryingBatch->aiDecisions()->latest('decided_at')->limit(10)->get();
        $actuatorLogs = $dryingBatch->actuatorLogs()->latest('executed_at')->limit(10)->get();

        $chartReadings = $dryingBatch->sensorReadings()
            ->valid()
            ->orderBy('recorded_at')
            ->limit(100)
            ->get();

        return view('batches.show', compact('dryingBatch', 'aiDecisions', 'actuatorLogs', 'chartReadings'));
    }

    public function edit(DryingBatch $dryingBatch): View
    {
        $devices = Device::orderBy('device_name')->get(['id', 'device_name']);
        return view('batches.edit', compact('dryingBatch', 'devices'));
    }

    public function update(Request $request, DryingBatch $dryingBatch): RedirectResponse
    {
        $data = $request->validate([
            'device_id'        => 'required|exists:devices,id',
            'batch_code'       => 'required|string|max:100|unique:drying_batches,batch_code,'.$dryingBatch->id,
            'rice_type'        => 'required|string|max:100',
            'rice_variety'     => 'nullable|string|max:100',
            'initial_weight'   => 'required|numeric|min:0',
            'current_weight'   => 'nullable|numeric|min:0',
            'initial_moisture' => 'required|numeric|min:0|max:100',
            'current_moisture' => 'nullable|numeric|min:0|max:100',
            'target_moisture'  => 'required|numeric|min:0|max:100',
            'drying_method'    => 'required|string|max:100',
            'operator_name'    => 'nullable|string|max:100',
            'status'           => 'required|in:waiting,drying,paused,completed,failed',
            'start_time'       => 'nullable|date',
            'end_time'         => 'nullable|date',
        ]);

        $dryingBatch->update($data);

        return redirect()->route('web.batches.show', $dryingBatch)->with('success', 'Batch berhasil diperbarui.');
    }

    public function destroy(DryingBatch $dryingBatch): RedirectResponse
    {
        $dryingBatch->delete();
        return redirect()->route('web.batches.index')->with('success', 'Batch berhasil dihapus.');
    }

    // ── Request Pengeringan dari Petani ──────────────────────────────────────

    /**
     * Daftar request pengeringan yang menunggu persetujuan (status pending).
     */
    public function pendingRequests(): View
    {
        $requests = DryingBatch::with(['requester', 'device'])
            ->where('request_status', 'pending')
            ->latest('requested_at')
            ->paginate(20);

        return view('batches.requests', compact('requests'));
    }

    /**
     * Approve request pengeringan dari petani.
     * Batch status berubah ke 'drying', request_status ke 'approved'.
     */
    public function approveRequest(Request $request, DryingBatch $dryingBatch): RedirectResponse
    {
        $data = $request->validate([
            'operator_notes' => 'nullable|string|max:500',
        ]);

        if ($dryingBatch->request_status !== 'pending') {
            return back()->withErrors(['error' => 'Request ini sudah diproses sebelumnya.']);
        }

        $dryingBatch->update([
            'request_status' => 'approved',
            'operator_notes' => $data['operator_notes'] ?? null,
            'operator_name'  => auth()->user()->name,
            'status'         => 'drying',
            'start_time'     => now(),
        ]);

        // Notifikasi ke petani
        if ($dryingBatch->requested_by) {
            \App\Models\Notification::create([
                'user_id'  => $dryingBatch->requested_by,
                'batch_id' => $dryingBatch->id,
                'type'     => 'success',
                'title'    => '✅ Permintaan Pengeringan Disetujui!',
                'message'  => 'Permintaan pengeringan gabah ' . $dryingBatch->rice_variety .
                              ' (' . $dryingBatch->initial_weight . ' kg) telah disetujui. ' .
                              'Mesin sudah mulai bekerja.' .
                              ($data['operator_notes'] ? ' Catatan operator: ' . $data['operator_notes'] : ''),
                'data'     => ['batch_id' => $dryingBatch->id, 'batch_code' => $dryingBatch->batch_code],
            ]);
        }

        return redirect()->route('web.batches.requests')
            ->with('success', 'Request disetujui. Batch ' . $dryingBatch->batch_code . ' mulai dikeringkan.');
    }

    /**
     * Reject request pengeringan dari petani.
     */
    public function rejectRequest(Request $request, DryingBatch $dryingBatch): RedirectResponse
    {
        $data = $request->validate([
            'operator_notes' => 'required|string|max:500',
        ]);

        if ($dryingBatch->request_status !== 'pending') {
            return back()->withErrors(['error' => 'Request ini sudah diproses sebelumnya.']);
        }

        $dryingBatch->update([
            'request_status' => 'rejected',
            'operator_notes' => $data['operator_notes'],
            'operator_name'  => auth()->user()->name,
            'status'         => 'failed',
        ]);

        // Notifikasi ke petani
        if ($dryingBatch->requested_by) {
            \App\Models\Notification::create([
                'user_id'  => $dryingBatch->requested_by,
                'batch_id' => $dryingBatch->id,
                'type'     => 'warning',
                'title'    => '❌ Permintaan Pengeringan Ditolak',
                'message'  => 'Permintaan pengeringan gabah ' . $dryingBatch->rice_variety .
                              ' (' . $dryingBatch->initial_weight . ' kg) tidak dapat diproses saat ini. ' .
                              'Alasan: ' . $data['operator_notes'],
                'data'     => ['batch_id' => $dryingBatch->id, 'batch_code' => $dryingBatch->batch_code],
            ]);
        }

        return redirect()->route('web.batches.requests')
            ->with('success', 'Request ditolak dan petani sudah diberitahu.');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filename = 'batches-'.now()->format('Ymd-His').'.xlsx';
        return Excel::download(new BatchExport($request), $filename);
    }

    public function exportCsv(Request $request): BinaryFileResponse
    {
        $filename = 'batches-'.now()->format('Ymd-His').'.csv';
        return Excel::download(new BatchExport($request), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportPdf(Request $request): Response
    {
        $query = DryingBatch::with('device')->latest();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('batch_code', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $batches  = $query->limit(500)->get();
        $pdf      = Pdf::loadView('exports.batches-pdf', compact('batches'))
                       ->setPaper('a4', 'landscape');

        return $pdf->download('batches-'.now()->format('Ymd-His').'.pdf');
    }
}

