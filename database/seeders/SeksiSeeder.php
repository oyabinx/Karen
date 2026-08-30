<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;

class SeksiSeeder extends Seeder
{
    /**
     * Contoh 2 seksi per bidang — disesuaikan admin setelah rilis.
     */
    public function run(): void
    {
        $map = [
            'Bidang Pemerintahan' => ['Seksi Ketatausahaan', 'Seksi Pemerintahan Desa'],
            'Bidang Perekonomian' => ['Seksi Perizinan', 'Seksi Pemberdayaan ekonomi'],
            'Bidang Pembangunan' => ['Seksi Infrastruktur', 'Seksi Pengendalian'],
            'Bidang Kesejahteraan' => ['Seksi Kesejahteraan sosial', 'Seksi Pendidikan'],
            'Bidang Umum' => ['Seksi Kepegawaian', 'Seksi Keuangan'],
        ];

        foreach ($map as $bidangName => $seksiNames) {
            $bidang = Bidang::where('name', $bidangName)->first();

            if (! $bidang) {
                continue;
            }

            foreach ($seksiNames as $name) {
                $bidang->seksi()->updateOrCreate(['name' => $name]);
            }
        }
    }
}
