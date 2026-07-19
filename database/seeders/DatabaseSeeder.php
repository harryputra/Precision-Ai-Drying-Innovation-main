<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Default `php artisan db:seed` = esensial + demo (untuk pengembangan lokal).
 * Produksi HANYA memakai EssentialSeeder (dipanggil entrypoint docker).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            EssentialSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
