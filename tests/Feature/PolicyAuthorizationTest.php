<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use App\Policies\AdministrationPolicy;
use App\Policies\CommunityPolicy;
use App\Policies\PositionPolicy;

test('community policy viewAny allows admin users', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $policy = new CommunityPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('community policy viewAny denies non-admin users', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $policy = new CommunityPolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

test('position policy viewAny allows admin users', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $policy = new PositionPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('position policy viewAny denies member with no manageable communities', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $policy = new PositionPolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

test('position policy viewAny allows president user', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);
    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'President']);
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
    $user->switchCommunity($community);
    $policy = new PositionPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('administration policy viewAny allows admin users', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $policy = new AdministrationPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

test('administration policy viewAny denies member with no manageable communities', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $policy = new AdministrationPolicy;

    expect($policy->viewAny($user))->toBeFalse();
});

test('administration policy viewAny allows president user', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);
    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'President']);
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
    $user->switchCommunity($community);
    $policy = new AdministrationPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});
