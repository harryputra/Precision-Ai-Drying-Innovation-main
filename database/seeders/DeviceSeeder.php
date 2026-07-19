<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        Device::truncate();

        $devices = [
            [
                'device_name'      => 'Padi PRECISION Unit 1',
                'serial_number'    => 'PADI-BNJ-001',
                'firmware_version' => 'v1.3.2',
                'ip_address'       => '192.168.1.101',
                'location'         => 'Kelompok Tani Maju Bersama — Margahurip, Banjaran',
                'status'           => 'online',
                'last_seen'        => now()->subMinutes(1),
            ],
            [
                'device_name'      => 'Padi PRECISION Unit 2',
                'serial_number'    => 'PADI-BNJ-002',
                'firmware_version' => 'v1.3.2',
                'ip_address'       => '192.168.1.102',
                'location'         => 'Kelompok Tani Maju Bersama — Margahurip, Banjaran',
                'status'           => 'online',
                'last_seen'        => now()->subMinutes(4),
            ],
        ];

        foreach ($devices as $d) {
            Device::create($d);
        }
    }
}
