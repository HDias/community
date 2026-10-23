<?php

namespace Database\Factories;

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referenceMonth = now()->startOfMonth();

        return [
            'community_id' => Community::factory(),
            'user_id' => User::factory(),
            'reference_month' => $referenceMonth,
            'monthly_key' => $referenceMonth,
            'amount' => fake()->randomFloat(2, 10, 200),
            'registered_by' => User::factory(),
            'type' => ContributionType::Monthly,
            'notes' => null,
        ];
    }
}
