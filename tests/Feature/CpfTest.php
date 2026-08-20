<?php

use App\Models\Profile;
use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;

test('valid cpf passes', function () {
    $rule = new Cpf;
    $failed = false;

    $rule->validate('cpf', '52998224725', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('cpf with all same digits fails', function () {
    $rule = new Cpf;
    $failed = false;

    $rule->validate('cpf', '11111111111', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('cpf with wrong length fails', function () {
    $rule = new Cpf;
    $failed = false;

    $rule->validate('cpf', '123', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('cpf with invalid check digits fails', function () {
    $rule = new Cpf;
    $failed = false;

    $rule->validate('cpf', '52998224720', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('cpf with formatting is stripped and validated', function () {
    $rule = new Cpf;
    $failed = false;

    $rule->validate('cpf', '529.982.247-25', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('cpf failure returns a human readable message', function () {
    $validator = Validator::make(['cpf' => '52998224720'], ['cpf' => new Cpf]);

    expect($validator->errors()->first('cpf'))
        ->toBe('The CPF field must be a valid CPF.');
});

test('profile factory generates valid cpfs', function () {
    $rule = new Cpf;

    for ($i = 0; $i < 10; $i++) {
        $profile = Profile::factory()->create();
        $failed = false;

        $rule->validate('cpf', $profile->cpf, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse("CPF {$profile->cpf} should be valid");
    }
});
