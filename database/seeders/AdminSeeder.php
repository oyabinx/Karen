<?php

namespace Database\Seeders;

use App\Models\Seksi;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun awal — PASSWORD WAJIB DIGANTI setelah login pertama.
     *
     * admin : manajemen user, organisasi, integrasi Google, event
     * pengurus : contoh user pengurus (kelola kendaraan, anggaran)
     * pegawai : contoh user pegawai (peminjaman)
     */
    public function run(): void
    {
        $seksiKepegawaian = Seksi::where('name', 'Seksi Kepegawaian')->first();
        $seksiInfrastruktur = Seksi::where('name', 'Seksi Infrastruktur')->first();

        User::updateOrCreate(
            ['email' => 'admin@karen.test'],
            [
                'name' => 'Administrator Karen',
                'password' => 'password',
                'role' => 'admin',
                'phone' => null,
                'seksi_id' => null,
            ],
        );

        if ($seksiKepegawaian) {
            User::updateOrCreate(
                ['email' => 'pengurus@karen.test'],
                [
                    'name' => 'Pengurus Contoh',
                    'password' => 'password',
                    'role' => 'pengurus',
                    'phone' => '081200000001',
                    'seksi_id' => $seksiKepegawaian->id,
                ],
            );
        }

        if ($seksiInfrastruktur) {
            User::updateOrCreate(
                ['email' => 'pegawai@karen.test'],
                [
                    'name' => 'Pegawai Contoh',
                    'password' => 'password',
                    'role' => 'pegawai',
                    'phone' => '081200000002',
                    'seksi_id' => $seksiInfrastruktur->id,
                ],
            );
        }
    }
}
