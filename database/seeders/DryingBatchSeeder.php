<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DryingBatch;
use Illuminate\Database\Seeder;

class DryingBatchSeeder extends Seeder
{
    public function run(): void
    {
        DryingBatch::truncate();

        $d1 = Device::where('serial_number', 'PADI-BNJ-001')->first();
        $d2 = Device::where('serial_number', 'PADI-BNJ-002')->first();

        $batches = [
            // Batch aktif — sedang dikeringkan (untuk screenshot dashboard)
            [
                'device_id'        => $d1->id,
                'batch_code'       => 'BNJ-2026-001',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'Ciherang',
                'initial_weight'   => 450.00,
                'current_weight'   => 428.50,
                'initial_moisture' => 24.20,
                'current_moisture' => 17.60,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Hybrid',
                'operator_name'    => 'Budi Santoso',
                'petani_name'      => 'Siti Rahayu',
                'petani_phone'     => '08123456789',
                'start_time'       => now()->subHours(5)->subMinutes(30),
                'end_time'         => null,
                'status'           => 'drying',
            ],
            // Batch kedua aktif — unit 2 (untuk menunjukkan multi-device)
            [
                'device_id'        => $d2->id,
                'batch_code'       => 'BNJ-2026-002',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'Mekongga',
                'initial_weight'   => 380.00,
                'current_weight'   => 368.00,
                'initial_moisture' => 22.80,
                'current_moisture' => 19.40,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Solar',
                'operator_name'    => 'Ahmad Fauzi',
                'petani_name'      => null,
                'petani_phone'     => null,
                'start_time'       => now()->subHours(2)->subMinutes(15),
                'end_time'         => null,
                'status'           => 'drying',
            ],
            // Batch selesai hari ini
            [
                'device_id'        => $d1->id,
                'batch_code'       => 'BNJ-2026-003',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'IR64',
                'initial_weight'   => 520.00,
                'current_weight'   => 491.80,
                'initial_moisture' => 23.50,
                'current_moisture' => 13.90,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Hybrid',
                'operator_name'    => 'Budi Santoso',
                'petani_name'      => 'Siti Rahayu',
                'petani_phone'     => '08123456789',
                'start_time'       => now()->subDays(1)->setTime(7, 30),
                'end_time'         => now()->subDays(1)->setTime(15, 45),
                'status'           => 'completed',
            ],
            // Batch sempat di-pause karena cuaca
            [
                'device_id'        => $d2->id,
                'batch_code'       => 'BNJ-2026-004',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'Ciherang',
                'initial_weight'   => 300.00,
                'current_weight'   => 287.00,
                'initial_moisture' => 25.00,
                'current_moisture' => 16.20,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Hybrid',
                'operator_name'    => 'Siti Rahayu',
                'petani_name'      => 'Siti Rahayu',
                'petani_phone'     => '08123456789',
                'start_time'       => now()->subDays(2)->setTime(8, 0),
                'end_time'         => now()->subDays(2)->setTime(17, 30),
                'status'           => 'completed',
            ],
            // Batch gagal (hujan deras)
            [
                'device_id'        => $d1->id,
                'batch_code'       => 'BNJ-2026-005',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'Inpari 32',
                'initial_weight'   => 200.00,
                'current_weight'   => 200.00,
                'initial_moisture' => 26.00,
                'current_moisture' => 25.80,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Solar',
                'operator_name'    => 'Ahmad Fauzi',
                'petani_name'      => null,
                'petani_phone'     => null,
                'start_time'       => now()->subDays(3)->setTime(9, 0),
                'end_time'         => now()->subDays(3)->setTime(10, 30),
                'status'           => 'failed',
            ],
            // Batch menunggu giliran
            [
                'device_id'        => $d2->id,
                'batch_code'       => 'BNJ-2026-006',
                'rice_type'        => 'Gabah',
                'rice_variety'     => 'Mekongga',
                'initial_weight'   => 500.00,
                'current_weight'   => null,
                'initial_moisture' => 23.00,
                'current_moisture' => null,
                'target_moisture'  => 14.00,
                'drying_method'    => 'Hybrid',
                'operator_name'    => 'Budi Santoso',
                'petani_name'      => 'Siti Rahayu',
                'petani_phone'     => '08123456789',
                'start_time'       => null,
                'end_time'         => null,
                'status'           => 'waiting',
            ],
        ];

        foreach ($batches as $b) {
            DryingBatch::create($b);
        }
    }
}
