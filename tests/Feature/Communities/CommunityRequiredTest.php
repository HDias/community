<?php

use App\Models\User;

test('position store without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->post(route('positions.store'), [
        'name' => 'Treasurer',
    ]);

    $response->assertSessionHasErrors('community');
});

test('administration store without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->post(route('administrations.store'), [
        'started_at' => now()->toDateString(),
        'ended_at' => now()->addYear()->toDateString(),
    ]);

    $response->assertSessionHasErrors('community');
});

test('member create form without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('members.create'));

    $response->assertSessionHasErrors('community');
});

test('member store without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->post(route('members.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpf' => '52998224725',
        'birth_date' => '1990-01-15',
    ]);

    $response->assertSessionHasErrors('community');
});

test('member edit form without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('members.edit', ['member' => $member->id]));

    $response->assertSessionHasErrors('community');
});

test('member update without community fails validation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();

    $response = $this->actingAs($admin)->put(route('members.update', ['member' => $member->id]), [
        'name' => 'John Doe',
        'email' => $member->email,
        'cpf' => '52998224725',
        'birth_date' => '1990-01-15',
    ]);

    $response->assertSessionHasErrors('community');
});
