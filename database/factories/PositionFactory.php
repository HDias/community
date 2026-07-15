<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_id' => Community::factory(),
            'name' => fake()->randomElement(['President', 'Vice-President', 'Secretary', 'Treasurer', 'Coordinator']),
            'is_default' => false,
            'has_admin_access' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
