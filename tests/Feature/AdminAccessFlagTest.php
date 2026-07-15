<?php

use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use App\Policies\AdministrationPolicy;
use App\Policies\CommunityPolicy;
use App\Policies\PositionPolicy;

function setupMemberWithPosition(bool $hasAdminAccess): array
{
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Test Role',
        'has_admin_access' => $hasAdminAccess,
    ]);

    $user = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    return [$user, $community, $administration, $position];
}

/*
|--------------------------------------------------------------------------
| has_admin_access flag on positions
|--------------------------------------------------------------------------
*/

test('position has_admin_access defaults to true', function () {
    $position = Position::factory()->create();

    expect($position->has_admin_access)->toBeTrue();
});

test('user with has_admin_access position is recognized as executive', function () {
    [$user, $community] = setupMemberWithPosition(true);

    expect($user->hasExecutivePositionIn($community))->toBeTrue();
});

test('user with non-admin-access position is not recognized as executive', function () {
    [$user, $community] = setupMemberWithPosition(false);

    expect($user->hasExecutivePositionIn($community))->toBeFalse();
});

test('user with has_admin_access position can view admin menus (viewAny)', function () {
    [$user, $community] = setupMemberWithPosition(true);
    $user->switchCommunity($community);

    expect((new CommunityPolicy)->viewAny($user))->toBeTrue();
    expect((new PositionPolicy)->viewAny($user))->toBeTrue();
    expect((new AdministrationPolicy)->viewAny($user))->toBeTrue();
});

test('user with non-admin-access position cannot view admin menus', function () {
    [$user, $community] = setupMemberWithPosition(false);
    $user->switchCommunity($community);

    expect((new CommunityPolicy)->viewAny($user))->toBeFalse();
    expect((new PositionPolicy)->viewAny($user))->toBeFalse();
    expect((new AdministrationPolicy)->viewAny($user))->toBeFalse();
});

test('user with non-admin-access position cannot update community', function () {
    [$user, $community] = setupMemberWithPosition(false);

    expect((new CommunityPolicy)->update($user, $community))->toBeFalse();
});

test('user with has_admin_access position can update community', function () {
    [$user, $community] = setupMemberWithPosition(true);

    expect((new CommunityPolicy)->update($user, $community))->toBeTrue();
});

test('user with non-admin-access position cannot manage positions', function () {
    [$user, $community] = setupMemberWithPosition(false);

    expect((new PositionPolicy)->manage($user, $community))->toBeFalse();
});

test('user with has_admin_access position can manage positions', function () {
    [$user, $community] = setupMemberWithPosition(true);

    expect((new PositionPolicy)->manage($user, $community))->toBeTrue();
});
