<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
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
            'order' => fake()->numberBetween(1, 10),
            'belong_to' => fake()->randomElement(['appointments', 'follow_ups']),
        ];
    }

    /**
     * Indicate the table the status belongs to.
     */
    public function belongingTo(string $table): static
    {
        return $this->state(fn (array $attributes) => [
            'belong_to' => $table,
        ]);
    }
}
