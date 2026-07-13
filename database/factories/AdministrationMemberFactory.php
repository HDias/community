<?php

namespace Database\Factories;

use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrationMember>
 */
class AdministrationMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'administration_id' => Administration::factory(),
            'user_id' => User::factory(),
            'position_id' => Position::factory(),
        ];
    }
}
