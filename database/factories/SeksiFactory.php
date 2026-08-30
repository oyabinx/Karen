<?php

namespace Database\Factories;

use App\Models\Bidang;
use App\Models\Seksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seksi>
 */
class SeksiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bidang_id' => Bidang::factory(),
            'name' => 'Seksi '.fake()->unique()->word(),
        ];
    }
}
