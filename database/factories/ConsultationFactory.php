<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Speciality;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'reason' => fake()->sentence(4),
            'anamnesis' => fake()->sentence(),
            'clinical_exam' => fake()->paragraph(),
            'assessment' => fake()->paragraph(),
            'diagnosis' => fake()->paragraph(),
            'treatment' => fake()->sentence(),
            'observations' => fake()->sentence(),
            'patient_id' => Patient::factory(),
            'appointment_id' => null,
            'type_id' => Type::factory()->belongingTo('consultations'),
            'speciality_id' => Speciality::factory(),
        ];
    }
}
