<?php

use App\Models\Profile;

test('profile factory generates plausible adult birth dates', function () {
    for ($i = 0; $i < 20; $i++) {
        $birthDate = Profile::factory()->create()->birth_date;

        expect($birthDate->age)->toBeGreaterThanOrEqual(18)
            ->and($birthDate->isPast())->toBeTrue();
    }
});

test('profile factory generates phones as brazilian mobile digits', function () {
    $phones = collect(range(1, 20))
        ->map(fn (): ?string => Profile::factory()->create()->phone)
        ->filter();

    expect($phones)->not->toBeEmpty();

    foreach ($phones as $phone) {
        expect($phone)->toMatch('/^[1-9][1-9]9\d{8}$/', "Phone {$phone} should be 11 digits with a valid area code");
    }
});

test('profile serializes birth date as Y-m-d for html date inputs', function () {
    $profile = Profile::factory()->create(['birth_date' => '1972-06-25']);

    expect($profile->toArray()['birth_date'])->toBe('1972-06-25')
        ->and($profile->birth_date->format('d/m/Y'))->toBe('25/06/1972');
});
