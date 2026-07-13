<?php

use App\Actions\Communities\CreateCommunity;
use App\Models\User;

test('creating a community seeds default positions', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $community = app(CreateCommunity::class)->handle($user, [
        'name' => 'Test Community',
    ]);

    expect($community->positions)->toHaveCount(4);
    expect($community->positions->pluck('name')->all())->toBe([
        'President', 'Vice-President', 'Secretary', 'Treasurer',
    ]);
    expect($community->positions->every(fn ($p) => $p->is_default))->toBeTrue();
});

test('creating a community creates first administration', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $community = app(CreateCommunity::class)->handle($user, [
        'name' => 'Test Community',
    ]);

    expect($community->administrations)->toHaveCount(1);
    expect($community->currentAdministration)->not->toBeNull();
    expect($community->currentAdministration->started_at->toDateString())->toBe(now()->toDateString());
    expect($community->currentAdministration->ended_at)->toBeNull();
});

test('creating a community assigns creator as president in first administration', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $community = app(CreateCommunity::class)->handle($user, [
        'name' => 'Test Community',
    ]);

    $administration = $community->currentAdministration;
    $members = $administration->members;

    expect($members)->toHaveCount(1);
    expect($members->first()->user_id)->toBe($user->id);
    expect($members->first()->position->name)->toBe('President');
});
