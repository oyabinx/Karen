<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Contoh armada untuk development — tahun pembuatan bervariasi
     * karena besaran anggaran 4 pos bergantung tahun (docs/feature/anggaran_maintenance.md).
     */
    public function run(): void
    {
        $vehicles = [
            ['name' => 'Avanza B 1234 XYZ', 'plate_number' => 'B 1234 XYZ', 'year' => 2020, 'capacity' => 7],
            ['name' => 'Innova B 5678 ABC', 'plate_number' => 'B 5678 ABC', 'year' => 2018, 'capacity' => 8],
            ['name' => 'Hiace B 9012 DEF', 'plate_number' => 'B 9012 DEF', 'year' => 2015, 'capacity' => 12],
            ['name' => 'Brio B 3456 GHI', 'plate_number' => 'B 3456 GHI', 'year' => 2022, 'capacity' => 5],
        ];

        foreach ($vehicles as $data) {
            Vehicle::updateOrCreate(
                ['plate_number' => $data['plate_number']],
                $data + ['status' => 'bisa_dipinjam', 'condition' => 'baik'],
            );
        }
    }
}
