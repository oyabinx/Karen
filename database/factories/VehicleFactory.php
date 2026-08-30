<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        static $n = 0;

        return [
            'name' => 'Kendaraan Uji '.(++$n),
            'plate_number' => 'B '.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT).' '.chr(random_int(65, 90)).chr(random_int(65, 90)).chr(random_int(65, 90)),
            'year' => random_int(2015, now()->year),
            'capacity' => random_int(4, 8),
            'status' => 'bisa_dipinjam',
            'condition' => 'baik',
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['status' => 'tidak_bisa_dipinjam']);
    }

    public function needsInspection(): static
    {
        return $this->state(fn () => ['condition' => 'perlu_diperiksa']);
    }
}
