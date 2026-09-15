<?php

namespace Database\Factories;

use App\Models\GuestReporter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestReporter>
 */
class GuestReporterFactory extends Factory
{
    protected $model = GuestReporter::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->boolean(80) ? '+2557'.fake()->numerify('########') : null,
            'email' => fake()->boolean(40) ? fake()->safeEmail() : null,
            'id_type' => fake()->boolean(50) ? fake()->randomElement(['National ID', 'Voter ID', 'Passport', 'Driving License']) : null,
            'id_number' => fake()->boolean(50) ? strtoupper(fake()->bothify('??######')) : null,
        ];
    }
}
