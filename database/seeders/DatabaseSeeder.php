<?php

namespace Database\Seeders;

use App\Actions\Communities\CreateCommunity;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // App admin — NOT a member of any community
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        // Community creator (president)
        $president = User::factory()->create([
            'email' => 'president@example.com',
        ]);

        // Community members
        $vicePresident = User::factory()->create();
        $secretary = User::factory()->create();
        $treasurer = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        // Brazilian cities mapped to their states
        $cityState1 = fake()->randomElement([
            ['city' => 'São Paulo', 'state' => 'SP'],
            ['city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['city' => 'Salvador', 'state' => 'BA'],
            ['city' => 'Recife', 'state' => 'PE'],
            ['city' => 'Belo Horizonte', 'state' => 'MG'],
            ['city' => 'Praia Grande', 'state' => 'SC'],
            ['city' => 'Curitiba', 'state' => 'PR'],
            ['city' => 'Fortaleza', 'state' => 'CE'],
        ]);

        // Create community (auto-creates 4 default positions + first administration with president)
        $community = app(CreateCommunity::class)->handle($president, [
            'name' => fake()->company().' Community',
            'description' => fake()->sentence(),
            'city' => $cityState1['city'],
            'state' => $cityState1['state'],
        ]);

        // Add members to the community
        $members = [$vicePresident, $secretary, $treasurer, $member1, $member2];
        foreach ($members as $member) {
            $community->members()->attach($member->id, [
                'role' => 'member',
                'joined_at' => now()->subMonths(rand(1, 12)),
            ]);
        }

        // Get default positions
        $positions = $community->positions;
        $presidentPosition = $positions->where('name', 'President')->first();
        $vpPosition = $positions->where('name', 'Vice-President')->first();
        $secretaryPosition = $positions->where('name', 'Secretary')->first();
        $treasurerPosition = $positions->where('name', 'Treasurer')->first();

        // Add a custom position
        $coordinatorPosition = $community->positions()->create([
            'name' => fake()->randomElement(['Coordinator', 'Director', 'Advisor', 'Counselor']),
            'is_default' => false,
        ]);

        /*
        |----------------------------------------------------------------------
        | Administration history
        |----------------------------------------------------------------------
        | 1st (2022-2024): ended, has members assigned
        | 2nd (2024-present): current, has members assigned
        */

        // End the auto-created first administration and backdate it
        $firstAdmin = $community->currentAdministration;
        $firstAdmin->update([
            'started_at' => '2022-01-15',
            'ended_at' => '2023-12-31',
        ]);

        // Assign members to first administration (historical)
        $firstAdmin->members()->where('user_id', $president->id)->update([
            'position_id' => $presidentPosition->id,
        ]);
        $firstAdmin->members()->create(['user_id' => $vicePresident->id, 'position_id' => $vpPosition->id]);
        $firstAdmin->members()->create(['user_id' => $secretary->id, 'position_id' => $secretaryPosition->id]);
        $firstAdmin->members()->create(['user_id' => $treasurer->id, 'position_id' => $treasurerPosition->id]);

        // Create second (current) administration
        $secondAdmin = $community->administrations()->create([
            'started_at' => '2024-01-01',
        ]);
        $community->update(['current_administration_id' => $secondAdmin->id]);

        // Assign members to current administration
        $secondAdmin->members()->create(['user_id' => $president->id, 'position_id' => $presidentPosition->id]);
        $secondAdmin->members()->create(['user_id' => $member1->id, 'position_id' => $vpPosition->id]);
        $secondAdmin->members()->create(['user_id' => $member2->id, 'position_id' => $secretaryPosition->id]);
        $secondAdmin->members()->create(['user_id' => $treasurer->id, 'position_id' => $treasurerPosition->id]);
        $secondAdmin->members()->create(['user_id' => $vicePresident->id, 'position_id' => $coordinatorPosition->id]);

        /*
        |----------------------------------------------------------------------
        | Second community — has members but NO administration members assigned
        |----------------------------------------------------------------------
        */

        $creator2 = User::factory()->create();

        $cityState2 = fake()->randomElement([
            ['city' => 'Porto Alegre', 'state' => 'RS'],
            ['city' => 'Manaus', 'state' => 'AM'],
            ['city' => 'Belém', 'state' => 'PA'],
            ['city' => 'Goiânia', 'state' => 'GO'],
            ['city' => 'Natal', 'state' => 'RN'],
            ['city' => 'Florianópolis', 'state' => 'SC'],
        ]);

        $community2 = app(CreateCommunity::class)->handle($creator2, [
            'name' => fake()->company().' Association',
            'description' => fake()->sentence(),
            'city' => $cityState2['city'],
            'state' => $cityState2['state'],
        ]);

        // Add some members but don't assign them to the administration
        $community2->members()->attach($member1->id, ['role' => 'member', 'joined_at' => now()->subMonths(3)]);
        $community2->members()->attach($member2->id, ['role' => 'member', 'joined_at' => now()->subMonths(2)]);
    }
}
