<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: bidang → seksi → user (butuh seksi) → kendaraan.
     */
    public function run(): void
    {
        $this->call([
            BidangSeeder::class,
            SeksiSeeder::class,
            AdminSeeder::class,
            VehicleSeeder::class,
        ]);
    }
}
