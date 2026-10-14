<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Breed;
use App\Models\Client;
use App\Models\Patient;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'sex' => fake()->randomElement(['Macho', 'Hembra']),
            'birth_date' => fake()->dateTimeBetween('-15 years', '-2 months')->format('Y-m-d'),
            'color' => fake()->safeColorName(),
            'weight' => fake()->randomFloat(2, 0.5, 60),
            'notes' => fake()->optional()->sentence(),
            'client_id' => Client::factory(),
            'species_id' => Species::factory(),
            'breed_id' => fn (array $attributes) => Breed::factory()->create([
                'species_id' => $attributes['species_id'],
            ])->getKey(),
        ];
    }
}
