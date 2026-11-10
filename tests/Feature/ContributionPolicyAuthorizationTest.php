<?php

use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\Position;
use App\Models\User;
use App\Policies\ContributionPolicy;

function communityWithPositionHolder(string $positionName): array
{
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => $positionName,
        'has_admin_access' => true,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    return [$user, $community];
}

function policyContribution(Community $community): Contribution
{
    return $community->contributions()->create([
        'user_id' => User::factory()->create()->id,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => now()->startOfMonth(),
        'amount' => 50,
        'registered_by' => User::factory()->create()->id,
        'type' => ContributionType::Monthly,
    ]);
}

dataset('contribution officers', ['President', 'Vice-President', 'Treasurer']);

test('create allows a system admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create();

    expect((new ContributionPolicy)->create($admin, $community))->toBeTrue();
});

test('create allows contribution officers', function (string $position) {
    [$user, $community] = communityWithPositionHolder($position);

    expect((new ContributionPolicy)->create($user, $community))->toBeTrue();
})->with('contribution officers');

test('create denies a Secretary with admin access', function () {
    [$user, $community] = communityWithPositionHolder('Secretary');

    expect($user->hasExecutivePositionIn($community))->toBeTrue()
        ->and((new ContributionPolicy)->create($user, $community))->toBeFalse();
});

test('create denies a plain member', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    expect((new ContributionPolicy)->create($user, $community))->toBeFalse();
});

test('create denies an officer of a previous administration', function () {
    $community = Community::factory()->create();
    $oldAdministration = Administration::factory()->create([
        'community_id' => $community->id,
        'ended_at' => now()->subMonth(),
    ]);
    $community->update(['current_administration_id' => null]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Treasurer',
        'has_admin_access' => true,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $oldAdministration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    expect((new ContributionPolicy)->create($user, $community))->toBeFalse();
});

test('revert allows a system admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create();
    $contribution = policyContribution($community);

    expect((new ContributionPolicy)->revert($admin, $contribution, $community))->toBeTrue();
});

test('revert allows contribution officers', function (string $position) {
    [$user, $community] = communityWithPositionHolder($position);
    $contribution = policyContribution($community);

    expect((new ContributionPolicy)->revert($user, $contribution, $community))->toBeTrue();
})->with('contribution officers');

test('revert denies a Secretary with admin access', function () {
    [$user, $community] = communityWithPositionHolder('Secretary');
    $contribution = policyContribution($community);

    expect((new ContributionPolicy)->revert($user, $contribution, $community))->toBeFalse();
});

test('revert denies a plain member', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
    $contribution = policyContribution($community);

    expect((new ContributionPolicy)->revert($user, $contribution, $community))->toBeFalse();
});

test('revert denies a contribution from another community', function () {
    [$user, $community] = communityWithPositionHolder('Treasurer');
    $otherCommunity = Community::factory()->create();
    $contribution = policyContribution($otherCommunity);

    expect((new ContributionPolicy)->revert($user, $contribution, $community))->toBeFalse();
});
