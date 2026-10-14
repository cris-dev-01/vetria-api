<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => 'rut',
            'document' => self::rut(),
            'address' => fake()->address(),
            'phone_number' => fake()->numerify('+569########'),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Generate a Chilean RUT with a valid check digit.
     */
    private static function rut(): string
    {
        $body = (string) fake()->numberBetween(5_000_000, 25_000_000);
        $sum = 0;
        $multiplier = 2;

        foreach (array_reverse(str_split($body)) as $digit) {
            $sum += (int) $digit * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $check = 11 - ($sum % 11);
        $dv = match ($check) {
            11 => '0',
            10 => 'K',
            default => (string) $check,
        };

        return number_format((int) $body, 0, '', '.').'-'.$dv;
    }
}
