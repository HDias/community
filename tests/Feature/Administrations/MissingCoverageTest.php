<?php

use App\Actions\Communities\CreateCommunity;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Cannot delete position in use by current administration
|--------------------------------------------------------------------------
*/

test('cannot delete position that is in use by current administration', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    // President position is in use by the first administration
    $presidentPosition = $community->positions()->where('name', 'President')->first();

    $this->actingAs($user)
        ->delete("/positions/{$presidentPosition->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Current president (non-admin, non-creator) can manage
|--------------------------------------------------------------------------
*/

test('current president can access positions without being admin or creator', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);

    // Make another user president in the current administration
    $president = User::factory()->create();
    $community->members()->attach($president->id, ['role' => 'president', 'joined_at' => now()]);
    $presidentPosition = $community->positions()->where('name', 'President')->first();
    $community->currentAdministration->members()->create([
        'user_id' => $president->id,
        'position_id' => $presidentPosition->id,
    ]);

    $this->actingAs($president)
        ->get('/positions?community='.$community->id)
        ->assertOk();
});

test('current president can access administrations without being admin or creator', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);

    $president = User::factory()->create();
    $community->members()->attach($president->id, ['role' => 'president', 'joined_at' => now()]);
    $presidentPosition = $community->positions()->where('name', 'President')->first();
    $community->currentAdministration->members()->create([
        'user_id' => $president->id,
        'position_id' => $presidentPosition->id,
    ]);

    $this->actingAs($president)
        ->get('/administrations?community='.$community->id)
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| App admin can update/delete any community
|--------------------------------------------------------------------------
*/

test('app admin can update a community they did not create', function () {
    $creator = User::factory()->create();
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Original']);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->put("/communities/{$community->id}", ['name' => 'Updated'])
        ->assertRedirect();

    expect($community->fresh()->name)->toBe('Updated');
});

test('app admin can delete a community they did not create', function () {
    $creator = User::factory()->create();
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'ToDelete']);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->delete("/communities/{$community->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('communities', ['id' => $community->id]);
});
