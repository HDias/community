<?php

use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('position factory creates valid records', function () {
    $position = Position::factory()->create();

    expect($position)
        ->name->not->toBeEmpty()
        ->community_id->not->toBeNull();

    $this->assertDatabaseHas('positions', ['id' => $position->id]);
});

test('position belongs to a community', function () {
    $community = Community::factory()->create();
    $position = Position::factory()->create(['community_id' => $community->id]);

    expect($position->community->id)->toBe($community->id);
});

test('administration factory creates valid records', function () {
    $administration = Administration::factory()->create();

    expect($administration)
        ->community_id->not->toBeNull()
        ->started_at->not->toBeNull();

    $this->assertDatabaseHas('administrations', ['id' => $administration->id]);
});

test('administration has members', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $position = Position::factory()->create(['community_id' => $community->id]);
    $user = User::factory()->create();

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);

    expect($administration->members)->toHaveCount(1);
    expect($administration->members->first()->user->id)->toBe($user->id);
});

test('a member can hold only one position per administration', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $user = User::factory()->create();
    $position1 = Position::factory()->create(['community_id' => $community->id]);
    $position2 = Position::factory()->create(['community_id' => $community->id]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position1->id,
    ]);

    expect(fn () => AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position2->id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('community has positions relationship', function () {
    $community = Community::factory()->create();
    Position::factory()->count(3)->create(['community_id' => $community->id]);

    expect($community->positions)->toHaveCount(3);
});

test('community has administrations relationship', function () {
    $community = Community::factory()->create();
    Administration::factory()->count(2)->create(['community_id' => $community->id]);

    expect($community->administrations)->toHaveCount(2);
});

test('community has current administration relationship', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    expect($community->fresh()->currentAdministration->id)->toBe($administration->id);
});
