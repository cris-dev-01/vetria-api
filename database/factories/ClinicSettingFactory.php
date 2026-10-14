<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClinicSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicSetting>
 */
class ClinicSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo_path' => 'logos/'.fake()->uuid().'.png',
            'primary_hex_color' => fake()->hexColor(),
            'phone_number' => fake()->numerify('+569########'),
            'address' => fake()->address(),
        ];
    }
}
