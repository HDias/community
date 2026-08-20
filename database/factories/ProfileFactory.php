<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Area codes (DDD) actually in use in Brazil.
     *
     * @var list<int>
     */
    private const AREA_CODES = [
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 24, 27, 28,
        31, 32, 33, 34, 35, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48, 49,
        51, 53, 54, 55,
        61, 62, 63, 64, 65, 66, 67, 68, 69,
        71, 73, 74, 75, 77, 79,
        81, 82, 83, 84, 85, 86, 87, 88, 89,
        91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'social_name' => fake()->optional()->name(),
            'nickname' => fake()->optional()->firstName(),
            'cpf' => self::generateValidCpf(),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->boolean(70) ? self::generatePhone() : null,
            'profession' => fake()->optional()->jobTitle(),
            'address_street' => fake()->optional()->streetName(),
            'address_number' => fake()->optional()->buildingNumber(),
            'address_neighborhood' => fake()->optional()->citySuffix(),
            'address_city' => fake()->optional()->city(),
            'address_state' => fake()->optional()->stateAbbr(),
            'address_zip' => fake()->optional()->numerify('########'),
        ];
    }

    /**
     * Generate an 11-digit Brazilian mobile number (area code + 9 + 8 digits).
     */
    private static function generatePhone(): string
    {
        $areaCode = self::AREA_CODES[array_rand(self::AREA_CODES)];

        return $areaCode.'9'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a valid CPF with correct check digits.
     */
    private static function generateValidCpf(): string
    {
        do {
            $digits = [];
            for ($i = 0; $i < 9; $i++) {
                $digits[] = random_int(0, 9);
            }
        } while (count(array_unique($digits)) === 1);

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += $digits[$i] * (($t + 1) - $i);
            }
            $digits[] = ((10 * $sum) % 11) % 10;
        }

        return implode('', $digits);
    }
}
