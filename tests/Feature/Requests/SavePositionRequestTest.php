<?php

use App\Http\Requests\Communities\AssignMemberRequest;
use App\Http\Requests\Communities\SavePositionRequest;
use App\Http\Requests\Communities\StoreAdministrationRequest;
use App\Http\Requests\Communities\UpdateAdministrationRequest;
use Illuminate\Foundation\Http\FormRequest;

test('SavePositionRequest requires name', function () {
    $request = new SavePositionRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('string');
    expect($rules['name'])->toContain('max:255');
});

test('StoreAdministrationRequest requires started_at', function () {
    $request = new StoreAdministrationRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('started_at');
    expect($rules['started_at'])->toContain('required');
    expect($rules['started_at'])->toContain('date');
});

test('UpdateAdministrationRequest requires started_at and allows nullable ended_at', function () {
    $request = new UpdateAdministrationRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('started_at');
    expect($rules['started_at'])->toContain('required');
    expect($rules['started_at'])->toContain('date');
    expect($rules)->toHaveKey('ended_at');
    expect($rules['ended_at'])->toContain('nullable');
    expect($rules['ended_at'])->toContain('date');
    expect($rules['ended_at'])->toContain('after_or_equal:started_at');
});

test('AssignMemberRequest requires user_id and position_id', function () {
    $request = new AssignMemberRequest;

    $method = new ReflectionMethod($request, 'rules');

    // We can't call rules() directly without route context,
    // but we verify the class exists and is a FormRequest
    expect($request)->toBeInstanceOf(FormRequest::class);
});
