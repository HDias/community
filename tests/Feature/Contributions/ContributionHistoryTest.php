<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachHistoryMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

function makeHistoryContribution(Community $community, User $member, User $registrar): Contribution
{
    return $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => now()->startOfMonth(),
        'amount' => 50,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Monthly,
    ]);
}

test('the monthly history exposes every cycle in chronological order', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Treasurer']);
    $reverser = User::factory()->create(['is_admin' => true, 'name' => 'Auditor']);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachHistoryMember($community, $member);

    $first = makeHistoryContribution($community, $member, $admin);
    $first->forceFill(['created_at' => now()->subDays(2)])->save();
    $first->forceFill([
        'reversed_at' => now()->subDay(),
        'reversed_by' => $reverser->id,
        'reversal_reason' => 'Wrong amount.',
        'monthly_key' => null,
    ])->save();

    $second = makeHistoryContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('members.data.0.history', 2)
            ->where('members.data.0.history.0.id', $first->id)
            ->where('members.data.0.history.0.amount', '50.00')
            ->where('members.data.0.history.0.status', 'reversed')
            ->where('members.data.0.history.0.registered_by_name', 'Treasurer')
            ->whereNot('members.data.0.history.0.registered_at', null)
            ->where('members.data.0.history.0.reversed_by_name', 'Auditor')
            ->where('members.data.0.history.0.reversal_reason', 'Wrong amount.')
            ->whereNot('members.data.0.history.0.reversed_at', null)
            ->where('members.data.0.history.1.id', $second->id)
            ->where('members.data.0.history.1.status', 'active')
            ->where('members.data.0.history.1.reversed_at', null)
            ->where('members.data.0.history.1.reversal_reason', null)
        );
});

test('a member without monthly payments has an empty history', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Bruno Costa']);
    attachHistoryMember($community, $member);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.data.0.name', 'Bruno Costa')
            ->has('members.data.0.history', 0)
        );
});

test('the monthly history ignores donations', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachHistoryMember($community, $member);

    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => null,
        'amount' => 100,
        'registered_by' => $admin->id,
        'type' => ContributionType::Donation,
        'donor_type' => 'member',
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('members.data.0.history', 0)
            ->where('members.data.0.paid', false)
        );
});

test('the monthly history only includes the selected month', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachHistoryMember($community, $member);

    $previousMonth = now()->subMonth()->startOfMonth();
    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => $previousMonth,
        'monthly_key' => $previousMonth,
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('members.data.0.history', 0)
        );
});
