<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'due_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'completed_at' => null,
            'patient_id' => Patient::factory(),
            'consultation_id' => Consultation::factory(),
            'assigned_to' => User::factory(),
            'type_id' => Type::factory()->belongingTo('follow_ups'),
            'status_id' => Status::factory()->belongingTo('follow_ups'),
        ];
    }
}
