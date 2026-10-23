<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;

function attachRegisterContributionMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

function attachRegisterContributionExecutive(Community $community, User $user): void
{
    attachRegisterContributionMember($community, $user);

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

function attachRegisterContributionNonExecutive(Community $community, User $user): void
{
    attachRegisterContributionMember($community, $user);

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

test('admin can register a monthly payment for a member', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $referenceMonth = now()->startOfMonth()->format('Y-m-d');

    $this->actingAs($admin)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => $referenceMonth,
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    $contribution = $community->contributions()->where('user_id', $member->id)->first();

    expect($contribution)->not->toBeNull()
        ->and($contribution->registered_by)->toBe($admin->id)
        ->and($contribution->type)->toBe(ContributionType::Monthly)
        ->and($contribution->monthly_key->format('Y-m-d'))->toBe($referenceMonth);
});

test('executive can register a monthly payment', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachRegisterContributionExecutive($community, $executive);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $this->actingAs($executive)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    expect($community->contributions()->count())->toBe(1);
});

test('non-executive position holder cannot register a payment', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachRegisterContributionNonExecutive($community, $nonExecutive);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $this->actingAs($nonExecutive)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});

test('plain member cannot register a payment', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachRegisterContributionMember($community, $plainMember);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $this->actingAs($plainMember)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});

test('cross-community executive cannot register a payment in another community', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachRegisterContributionExecutive(Community::factory()->create(), $outsider);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $this->actingAs($outsider)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertForbidden();
});

test('registering a duplicate month for the same member fails validation', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $referenceMonth = now()->startOfMonth();

    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    $this->actingAs($admin)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => $referenceMonth->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('reference_month');

    expect($community->contributions()->count())->toBe(1);
});

test('user_id not belonging to the resolved community fails validation', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $outsider = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $outsider->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('user_id');
});

test('amount resolved matches the contribution setting effective for that month, not the latest one', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachRegisterContributionMember($community, $member);

    $community->contributionSettings()->create([
        'monthly_amount' => 30,
        'effective_from' => now()->subMonths(3)->startOfMonth(),
    ]);
    $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => now()->startOfMonth(),
    ]);

    $pastMonth = now()->subMonths(2)->startOfMonth()->format('Y-m-d');

    $this->actingAs($admin)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => $pastMonth,
        ])
        ->assertRedirect();

    $contribution = $community->contributions()->where('user_id', $member->id)->first();

    expect((float) $contribution->amount)->toBe(30.0);
});
