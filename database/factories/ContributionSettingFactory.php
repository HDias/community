<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\ContributionSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionSetting>
 */
class ContributionSettingFactory extends Factory
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
            'monthly_amount' => fake()->randomFloat(2, 10, 200),
            'effective_from' => now()->startOfMonth(),
        ];
    }
}
