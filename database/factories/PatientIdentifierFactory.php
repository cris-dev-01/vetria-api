<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientIdentifier>
 */
class PatientIdentifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => fake()->unique()->numerify('###############'),
            'type_id' => Type::factory()->belongingTo('patient_identifiers'),
            'patient_id' => Patient::factory(),
        ];
    }
}
