<?php

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('community factory creates valid records', function () {
    $community = Community::factory()->create();

    expect($community)
        ->name->not->toBeEmpty()
        ->slug->not->toBeEmpty()
        ->created_by->not->toBeNull();

    $this->assertDatabaseHas('communities', ['id' => $community->id]);
});

test('slug is auto-generated from name when not provided', function () {
    $user = User::factory()->create();

    $community = Community::create([
        'name' => 'My Test Community',
        'created_by' => $user->id,
    ]);

    expect($community->slug)->toBe('my-test-community');
});

test('slug is unique including soft-deleted records', function () {
    $user = User::factory()->create();

    $first = Community::create([
        'name' => 'Duplicate Name',
        'created_by' => $user->id,
    ]);
    $first->delete();

    $second = Community::create([
        'name' => 'Duplicate Name',
        'created_by' => $user->id,
    ]);

    expect($second->slug)->toBe('duplicate-name-1');
});

test('community has a creator relationship', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $user->id]);

    expect($community->creator->id)->toBe($user->id);
});

test('community has members relationship', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create();

    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    expect($community->members)->toHaveCount(1);
    expect($community->members->first()->pivot->role)->toBe('member');
});

test('user can belong to multiple communities', function () {
    $user = User::factory()->create();
    $communities = Community::factory(3)->create();

    foreach ($communities as $community) {
        $community->members()->attach($user->id, [
            'role' => CommunityRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    expect($user->communities)->toHaveCount(3);
});

test('user can switch current community', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();

    $user->switchCommunity($community);

    expect($user->fresh()->current_community_id)->toBe($community->id);
    expect($user->currentCommunity->id)->toBe($community->id);
});

test('user belongs to community check works', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();
    $otherCommunity = Community::factory()->create();

    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
    ]);

    expect($user->belongsToCommunity($community))->toBeTrue();
    expect($user->belongsToCommunity($otherCommunity))->toBeFalse();
});

test('community user pivot enforces unique constraint', function () {
    $community = Community::factory()->create();
    $user = User::factory()->create();

    $community->members()->attach($user->id, ['role' => CommunityRole::Member->value]);

    expect(fn () => $community->members()->attach($user->id, ['role' => CommunityRole::Admin->value]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('community soft deletes preserve slug uniqueness', function () {
    $user = User::factory()->create();

    $community = Community::create([
        'name' => 'Soft Delete Test',
        'created_by' => $user->id,
    ]);

    expect($community->slug)->toBe('soft-delete-test');

    $community->delete();
    expect(Community::withTrashed()->where('slug', 'soft-delete-test')->exists())->toBeTrue();
});
