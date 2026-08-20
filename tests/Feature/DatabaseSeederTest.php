<?php

use App\Models\User;

test('every seeded user has a profile with personal data', function () {
    $this->seed();

    $users = User::with('profile')->get();

    expect($users)->not->toBeEmpty();

    foreach ($users as $user) {
        expect($user->profile)->not->toBeNull("User {$user->email} should have a profile")
            ->and($user->profile->birth_date)->not->toBeNull()
            ->and($user->profile->cpf)->not->toBeEmpty();
    }
});

test('every seeded community member is listed with a birth date', function () {
    $this->seed();

    $members = User::query()
        ->whereHas('communities')
        ->with('profile')
        ->get();

    expect($members)->not->toBeEmpty();

    foreach ($members as $member) {
        expect($member->profile?->birth_date?->format('Y-m-d'))
            ->not->toBeNull("Member {$member->email} should have a birth date");
    }
});
