<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationVital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationVital>
 */
class ConsultationVitalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'weight' => fake()->randomFloat(2, 0.5, 60),
            'temperature' => fake()->randomFloat(1, 37.5, 39.5),
            'heart_rate' => fake()->numberBetween(60, 180),
            'respiratory_rate' => fake()->numberBetween(10, 40),
            'body_condition_score' => fake()->numberBetween(1, 9),
            'notes' => fake()->optional()->sentence(),
            'consultation_id' => Consultation::factory(),
        ];
    }
}
