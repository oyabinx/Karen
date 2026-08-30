<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;

class BidangSeeder extends Seeder
{
    /**
     * 5 bidang awal kantor — NAMA DAPAT DISESUAIKAN admin setelah rilis
     * (docs/feature/organisasi.md). Satu bidang khusus diberi kuota 3
     * mobil (max_active_bookings), sisanya default 2.
     */
    public function run(): void
    {
        $bidang = [
            ['name' => 'Bidang Pemerintahan', 'max_active_bookings' => 2],
            ['name' => 'Bidang Perekonomian', 'max_active_bookings' => 2],
            ['name' => 'Bidang Pembangunan', 'max_active_bookings' => 2],
            ['name' => 'Bidang Kesejahteraan', 'max_active_bookings' => 3], // bidang khusus kuota 3
            ['name' => 'Bidang Umum', 'max_active_bookings' => 2],
        ];

        foreach ($bidang as $data) {
            Bidang::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
