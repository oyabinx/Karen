<?php

namespace Database\Factories;

use App\Models\Bidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bidang>
 */
class BidangFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Bidang '.fake()->unique()->word(),
            'max_active_bookings' => 2,
        ];
    }
}
