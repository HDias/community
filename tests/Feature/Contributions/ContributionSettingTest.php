<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Attach a user to a community as a plain member.
 */
function attachContributionMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

/**
 * Attach a user to a community holding a position with admin access.
 */
function attachContributionExecutive(Community $community, User $user): void
{
    attachContributionMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'has_admin_access' => true,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

/**
 * Attach a user to a community holding a position without admin access.
 */
function attachContributionNonExecutive(Community $community, User $user): void
{
    attachContributionMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'has_admin_access' => false,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

test('admin can view settings and change the monthly amount', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.settings.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/settings')
            ->where('community', [
                'id' => $community->id,
                'name' => $community->name,
                'slug' => $community->slug,
            ])
            ->has('settings', 0)
        );

    $this->actingAs($admin)
        ->post(route('contributions.settings.store', ['community' => $community->id]), [
            'monthly_amount' => 75,
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertRedirect(route('contributions.settings.index', ['community' => $community->id]));

    expect($community->contributionSettings()->count())->toBe(1);
});

test('executive with admin access can view and change settings, preserving history', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachContributionExecutive($community, $executive);

    $community->contributionSettings()->create([
        'monthly_amount' => 30,
        'effective_from' => now()->subMonth()->startOfMonth(),
    ]);

    $this->actingAs($executive)
        ->get(route('contributions.settings.index', ['community' => $community->id]))
        ->assertOk();

    $this->actingAs($executive)
        ->post(route('contributions.settings.store', ['community' => $community->id]), [
            'monthly_amount' => 50,
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertRedirect(route('contributions.settings.index', ['community' => $community->id]));

    expect($community->contributionSettings()->count())->toBe(2)
        ->and((float) $community->contributionSettings()->orderBy('effective_from')->first()->monthly_amount)->toBe(30.0);
});

test('non-executive position holder cannot view or change settings', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    attachContributionNonExecutive($community, $member);

    $this->actingAs($member)
        ->get(route('contributions.settings.index', ['community' => $community->id]))
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('contributions.settings.store', ['community' => $community->id]), [
            'monthly_amount' => 50,
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});

test('plain member cannot view or change settings', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    attachContributionMember($community, $member);

    $this->actingAs($member)
        ->get(route('contributions.settings.index', ['community' => $community->id]))
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('contributions.settings.store', ['community' => $community->id]), [
            'monthly_amount' => 50,
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});

test('cross-community executive cannot view or change another community settings', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachContributionExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->get(route('contributions.settings.index', ['community' => $community->id]))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->post(route('contributions.settings.store', ['community' => $community->id]), [
            'monthly_amount' => 50,
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});
