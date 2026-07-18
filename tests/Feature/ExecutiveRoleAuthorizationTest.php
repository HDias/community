<?php

use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use App\Policies\AdministrationPolicy;
use App\Policies\CommunityPolicy;
use App\Policies\PositionPolicy;

/*
|--------------------------------------------------------------------------
| Helper: Setup a community with a current administration and a member
|--------------------------------------------------------------------------
*/
function createCommunityWithExecutiveMember(string $positionName): array
{
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => $positionName,
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    return [$user, $community, $administration];
}

/*
|--------------------------------------------------------------------------
| User::hasExecutivePositionIn() method
|--------------------------------------------------------------------------
*/
test('user with President position in current administration is executive', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');

    expect($user->hasExecutivePositionIn($community))->toBeTrue();
});

test('user with Vice-President position in current administration is executive', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Vice-President');

    expect($user->hasExecutivePositionIn($community))->toBeTrue();
});

test('user with Secretary position in current administration is executive', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Secretary');

    expect($user->hasExecutivePositionIn($community))->toBeTrue();
});

test('user with Treasurer position in current administration is executive', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Treasurer');

    expect($user->hasExecutivePositionIn($community))->toBeTrue();
});

test('user with non-admin-access position is not executive', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Custom Role',
        'has_admin_access' => false,
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    expect($user->hasExecutivePositionIn($community))->toBeFalse();
});

test('user with no position in current administration is not executive', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    expect($user->hasExecutivePositionIn($community))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| User::hasLeadershipPositionIn() - President or Secretary only
|--------------------------------------------------------------------------
*/
test('President has leadership position', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');

    expect($user->hasLeadershipPositionIn($community))->toBeTrue();
});

test('Secretary has leadership position', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Secretary');

    expect($user->hasLeadershipPositionIn($community))->toBeTrue();
});

test('Vice-President has leadership position', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Vice-President');

    expect($user->hasLeadershipPositionIn($community))->toBeTrue();
});

test('Treasurer has leadership position', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Treasurer');

    expect($user->hasLeadershipPositionIn($community))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| CommunityPolicy
|--------------------------------------------------------------------------
*/
test('community policy viewAny allows executive role holders', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Treasurer');
    $user->switchCommunity($community);

    $policy = new CommunityPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('community policy create only allows system admin', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');
    $user->switchCommunity($community);

    $policy = new CommunityPolicy;

    expect($policy->create($user))->toBeFalse();
});

test('community policy update allows executive roles for their community', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Treasurer');

    $policy = new CommunityPolicy;

    expect($policy->update($user, $community))->toBeTrue();
});

test('community policy update denies regular member', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $policy = new CommunityPolicy;

    expect($policy->update($user, $community))->toBeFalse();
});

test('community policy delete only allows system admin', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');

    $policy = new CommunityPolicy;

    expect($policy->delete($user, $community))->toBeFalse();
});

test('community policy delete allows system admin', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create();

    $policy = new CommunityPolicy;

    expect($policy->delete($user, $community))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| AdministrationPolicy
|--------------------------------------------------------------------------
*/
test('administration policy viewAny allows executive role holders', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Vice-President');
    $user->switchCommunity($community);

    $policy = new AdministrationPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('administration policy manage allows President for current administration', function () {
    [$user, $community, $administration] = createCommunityWithExecutiveMember('President');

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $administration))->toBeTrue();
});

test('administration policy manage allows Secretary for current administration', function () {
    [$user, $community, $administration] = createCommunityWithExecutiveMember('Secretary');

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $administration))->toBeTrue();
});

test('administration policy manage allows Vice-President for current administration', function () {
    [$user, $community, $administration] = createCommunityWithExecutiveMember('Vice-President');

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $administration))->toBeTrue();
});

test('administration policy manage allows Treasurer for current administration', function () {
    [$user, $community, $administration] = createCommunityWithExecutiveMember('Treasurer');

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $administration))->toBeTrue();
});

test('administration policy manage denies non-admin for old administration', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');

    $oldAdministration = Administration::factory()->create([
        'community_id' => $community->id,
        'ended_at' => now()->subMonth(),
    ]);

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $oldAdministration))->toBeFalse();
});

test('administration policy manage allows system admin for old administration', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create();
    $oldAdministration = Administration::factory()->create([
        'community_id' => $community->id,
        'ended_at' => now()->subMonth(),
    ]);

    $policy = new AdministrationPolicy;

    expect($policy->manage($user, $community, $oldAdministration))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| PositionPolicy
|--------------------------------------------------------------------------
*/
test('position policy viewAny allows executive role holders', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Secretary');
    $user->switchCommunity($community);

    $policy = new PositionPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('position policy manage allows executive roles', function () {
    [$user, $community] = createCommunityWithExecutiveMember('Treasurer');

    $policy = new PositionPolicy;

    expect($policy->manage($user, $community))->toBeTrue();
});

test('position policy manage denies regular member', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $policy = new PositionPolicy;

    expect($policy->manage($user, $community))->toBeFalse();
});

test('position policy delete denies executive role holders', function () {
    [$user, $community] = createCommunityWithExecutiveMember('President');

    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'Unused']);

    $policy = new PositionPolicy;

    expect($policy->delete($user, $position))->toBeFalse();
});

test('position policy delete allows system admin', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create();
    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'Unused']);

    $policy = new PositionPolicy;

    expect($policy->delete($user, $position))->toBeTrue();
});
