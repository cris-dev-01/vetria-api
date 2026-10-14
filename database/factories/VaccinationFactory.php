<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Vaccination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vaccination>
 */
class VaccinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vaccine_id' => null,
            'application_date' => $applied = fake()->dateTimeBetween('-1 year', 'now'),
            'next_due_date' => (clone $applied)->modify('+1 year')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'consultation_id' => Consultation::factory(),
        ];
    }
}
