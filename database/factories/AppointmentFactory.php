<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Status;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => 'APT-'.strtoupper(Str::random(8)),
            'schedule_at' => fake()->dateTimeBetween('now', '+2 months'),
            'duration' => fake()->randomElement(['00:15:00', '00:30:00', '00:45:00', '01:00:00']),
            'notes' => fake()->optional()->sentence(),
            'patient_id' => Patient::factory(),
            'type_id' => Type::factory()->belongingTo('consultations'),
            'status_id' => Status::factory()->belongingTo('appointments'),
        ];
    }
}
