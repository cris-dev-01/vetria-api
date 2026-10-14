<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Type>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->words(2, true),
            'code' => Str::snake($name),
            'belong_to' => fake()->randomElement(['consultations', 'follow_ups', 'patient_identifiers']),
        ];
    }

    /**
     * Indicate the table the type belongs to.
     */
    public function belongingTo(string $table): static
    {
        return $this->state(fn (array $attributes) => [
            'belong_to' => $table,
        ]);
    }
}
