<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\ContributionReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionReport>
 */
class ContributionReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalMembers = fake()->numberBetween(5, 50);
        $totalPaid = fake()->numberBetween(0, $totalMembers);

        return [
            'community_id' => Community::factory(),
            'reference_month' => now()->startOfMonth(),
            'total_members' => $totalMembers,
            'total_paid' => $totalPaid,
            'total_defaulting' => $totalMembers - $totalPaid,
            'total_collected' => fake()->randomFloat(2, 0, 5000),
            'generated_at' => now(),
        ];
    }
}
