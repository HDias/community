<?php

use App\Actions\Administrations\AssignMemberToPosition;
use App\Actions\Administrations\CreateAdministration;
use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;

test('create administration ends the previous one', function () {
    $community = Community::factory()->create();
    $first = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $first->id]);

    $second = app(CreateAdministration::class)->handle($community->fresh(), [
        'started_at' => now()->addYear(),
    ]);

    expect($first->fresh()->ended_at)->not->toBeNull();
    expect($second->ended_at)->toBeNull();
    expect($community->fresh()->current_administration_id)->toBe($second->id);
});

test('create administration works when no previous administration exists', function () {
    $community = Community::factory()->create();

    $administration = app(CreateAdministration::class)->handle($community, [
        'started_at' => now(),
    ]);

    expect($administration)->toBeInstanceOf(Administration::class);
    expect($community->fresh()->current_administration_id)->toBe($administration->id);
});

test('assign member to position creates administration member', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);
    $position = Position::factory()->create(['community_id' => $community->id]);
    $user = User::factory()->create();
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $member = app(AssignMemberToPosition::class)->handle($administration, $user, $position);

    expect($member->user_id)->toBe($user->id);
    expect($member->position_id)->toBe($position->id);
    expect($member->administration_id)->toBe($administration->id);
});

test('assign member to position updates existing assignment', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);
    $position1 = Position::factory()->create(['community_id' => $community->id, 'name' => 'Secretary']);
    $position2 = Position::factory()->create(['community_id' => $community->id, 'name' => 'Treasurer']);
    $user = User::factory()->create();
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    app(AssignMemberToPosition::class)->handle($administration, $user, $position1);
    $member = app(AssignMemberToPosition::class)->handle($administration, $user, $position2);

    expect($administration->members()->where('user_id', $user->id)->count())->toBe(1);
    expect($member->position_id)->toBe($position2->id);
});

test('assigning president position syncs community_user role', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);
    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'President']);
    $user = User::factory()->create();
    $community->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    app(AssignMemberToPosition::class)->handle($administration, $user, $position);

    expect($community->members()->where('user_id', $user->id)->first()->pivot->role)
        ->toBe(CommunityRole::President->value);
});
