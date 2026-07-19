<?php

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\DryingBatch;
use App\Models\Notification;
use App\Models\User;

/**
 * NotificationService — kirim notifikasi targeted ke petani berdasarkan batch.
 *
 * Mapping petani → user:
 *   batch.petani_name di-match ke users.name (case-insensitive, partial match)
 *   Jika tidak ketemu → kirim ke semua user role=viewer sebagai fallback
 *   Selalu kirim ke semua admin+operator untuk awareness
 */
class NotificationService
{
    /**
     * Notifikasi gabah selesai — targeted ke petani pemilik batch.
     */
    public function batchCompleted(DryingBatch $batch): void
    {
        $this->sendToBatch(
            batch: $batch,
            type: 'success',
            category: 'batch_complete',
            title: '✅ Gabah Kamu Sudah Kering!',
            message: "Gabah {$batch->rice_variety} (Batch {$batch->batch_code}) sudah selesai dikeringkan. "
                   . "Kadar air mencapai {$batch->current_moisture}%. Silakan ambil gabah di lokasi mesin.",
            data: [
                'batch_id'         => $batch->id,
                'batch_code'       => $batch->batch_code,
                'current_moisture' => $batch->current_moisture,
                'target_moisture'  => $batch->target_moisture,
            ]
        );
    }

    /**
     * Notifikasi pengeringan dijeda — biasanya karena hujan.
     */
    public function batchPaused(DryingBatch $batch, string $reason = 'Cuaca tidak mendukung'): void
    {
        $this->sendToBatch(
            batch: $batch,
            type: 'warning',
            category: 'weather_alert',
            title: '🌧️ Pengeringan Dijeda Otomatis',
            message: "Pengeringan gabah {$batch->rice_variety} dijeda sementara. "
                   . "Alasan: {$reason}. Sistem akan melanjutkan otomatis saat kondisi membaik.",
            data: [
                'batch_id'   => $batch->id,
                'batch_code' => $batch->batch_code,
                'reason'     => $reason,
            ]
        );
    }

    /**
     * Notifikasi kondisi kritis (suhu tinggi, kelembaban ekstrem, dll).
     */
    public function criticalAlert(DryingBatch $batch, string $alertMessage): void
    {
        $this->sendToBatch(
            batch: $batch,
            type: 'alert',
            category: 'temperature_alert',
            title: '🚨 Perhatian — Kondisi Perlu Ditangani',
            message: $alertMessage . ' Petugas BUMDes sudah diberitahu.',
            data: [
                'batch_id'   => $batch->id,
                'batch_code' => $batch->batch_code,
            ]
        );
    }

    /**
     * Notifikasi pengeringan dilanjutkan setelah pause.
     */
    public function batchResumed(DryingBatch $batch): void
    {
        $this->sendToBatch(
            batch: $batch,
            type: 'info',
            category: 'batch_complete',
            title: '🔥 Pengeringan Dilanjutkan',
            message: "Pengeringan gabah {$batch->rice_variety} dilanjutkan kembali. Kondisi cuaca sudah aman.",
            data: ['batch_id' => $batch->id]
        );
    }

    /**
     * Core: kirim notifikasi ke petani pemilik batch + semua admin/operator.
     */
    private function sendToBatch(
        DryingBatch $batch,
        string $type,
        string $category,
        string $title,
        string $message,
        array $data = []
    ): void {
        $recipients = collect();

        // 1. Cari user berdasarkan petani_name di batch
        if (!empty($batch->petani_name)) {
            $petaniUsers = User::where('role', 'viewer')
                ->where('name', 'like', '%' . $batch->petani_name . '%')
                ->get();

            $recipients = $recipients->merge($petaniUsers);
        }

        // 2. Fallback: jika tidak ada match, kirim ke semua viewer
        if ($recipients->isEmpty()) {
            $viewers = User::where('role', 'viewer')->get();
            $recipients = $recipients->merge($viewers);
        }

        // 3. Selalu kirim ke admin + operator (awareness BUMDes)
        $staff = User::whereIn('role', ['admin', 'operator'])->get();
        $recipients = $recipients->merge($staff)->unique('id');

        // Kirim ke semua recipient
        foreach ($recipients as $user) {
            $notif = Notification::create([
                'user_id'   => $user->id,
                'device_id' => $batch->device_id,
                'batch_id'  => $batch->id,
                'type'      => $type,
                'category'  => $category,
                'title'     => $title,
                'message'   => $message,
                'data'      => $data,
                'via_app'   => true,
                'sent_at'   => now(),
            ]);

            // Broadcast realtime ke user yang online
            broadcast(new NotificationSent($notif));
        }
    }
}
