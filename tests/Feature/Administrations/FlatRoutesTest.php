<?php

use App\Actions\Communities\CreateCommunity;
use App\Models\Administration;
use App\Models\Community;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Positions — Flat routes with ?community= query param
|--------------------------------------------------------------------------
*/

test('positions index requires community query param to show positions', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/positions')
        ->assertOk();
});

test('positions index auto-redirects when user has single manageable community', function () {
    $user = User::factory()->create();
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Only One']);

    $this->actingAs($user)
        ->get('/positions')
        ->assertRedirect('/positions?community='.$community->id);
});

test('positions index does not redirect when user has multiple manageable communities', function () {
    $user = User::factory()->create();
    app(CreateCommunity::class)->handle($user, ['name' => 'First']);
    app(CreateCommunity::class)->handle($user, ['name' => 'Second']);

    $this->actingAs($user)
        ->get('/positions')
        ->assertOk();
});

test('positions index shows positions for selected community', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->get('/positions?community='.$community->id)
        ->assertOk();
});

test('non-authorized user cannot access positions for a community', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);

    $regularUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->get('/positions?community='.$community->id)
        ->assertForbidden();
});

test('app admin can access positions for any community without being a member', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);

    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($otherAdmin)
        ->get('/positions?community='.$community->id)
        ->assertOk();
});

test('can create position via flat route with community query param', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->post('/positions?community='.$community->id, ['name' => 'Coordinator'])
        ->assertRedirect();

    $this->assertDatabaseHas('positions', [
        'community_id' => $community->id,
        'name' => 'Coordinator',
    ]);
});

test('can update position via flat route', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $position = $community->positions()->create(['name' => 'Old']);

    $this->actingAs($user)
        ->put("/positions/{$position->id}", ['name' => 'New'])
        ->assertRedirect();

    expect($position->fresh()->name)->toBe('New');
});

test('can delete position via flat route', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $position = $community->positions()->create(['name' => 'Extra']);

    $this->actingAs($user)
        ->delete("/positions/{$position->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('positions', ['id' => $position->id]);
});

/*
|--------------------------------------------------------------------------
| Administrations — Flat routes with ?community= query param
|--------------------------------------------------------------------------
*/

test('administrations index requires community query param to show data', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/administrations')
        ->assertOk();
});

test('administrations index auto-redirects when user has single manageable community', function () {
    $user = User::factory()->create();
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Only One']);

    $this->actingAs($user)
        ->get('/administrations')
        ->assertRedirect('/administrations?community='.$community->id);
});

test('administrations index shows administrations for selected community', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->get('/administrations?community='.$community->id)
        ->assertOk();
});

test('app admin can access administrations without being a member', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);

    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($otherAdmin)
        ->get('/administrations?community='.$community->id)
        ->assertOk();
});

test('can create administration via flat route with community query param', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->post('/administrations?community='.$community->id, [
            'started_at' => now()->addYear()->toDateString(),
            'ended_at' => now()->addYears(2)->toDateString(),
        ])
        ->assertRedirect();

    expect($community->fresh()->administrations)->toHaveCount(2);
});

test('can view administration show via flat route', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->get("/administrations/{$administration->id}")
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| Administration dates — update
|--------------------------------------------------------------------------
*/

test('can update administration start date', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->put("/administrations/{$administration->id}", [
            'started_at' => '2024-01-15',
            'ended_at' => null,
        ])
        ->assertRedirect();

    expect($administration->fresh()->started_at->toDateString())->toBe('2024-01-15');
});

test('can end current administration with end_administration flag', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->put("/administrations/{$administration->id}", [
            'started_at' => $administration->started_at->toDateString(),
            'ended_at' => now()->addYear()->toDateString(),
            'end_administration' => true,
        ])
        ->assertRedirect();

    expect($administration->fresh()->ended_at)->not->toBeNull();
    expect($community->fresh()->current_administration_id)->toBeNull();
});

test('saving dates without end_administration flag does not end current administration', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->put("/administrations/{$administration->id}", [
            'started_at' => $administration->started_at->toDateString(),
            'ended_at' => now()->addYear()->toDateString(),
        ])
        ->assertRedirect();

    expect($administration->fresh()->ended_at)->not->toBeNull();
    expect($community->fresh()->current_administration_id)->toBe($administration->id);
});

test('ended_at must be after or equal to started_at', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->put("/administrations/{$administration->id}", [
            'started_at' => '2025-06-01',
            'ended_at' => '2025-01-01',
        ])
        ->assertSessionHasErrors('ended_at');
});

/*
|--------------------------------------------------------------------------
| Administration — delete
|--------------------------------------------------------------------------
*/

test('can delete an administration', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->delete("/administrations/{$administration->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('administrations', ['id' => $administration->id]);
});

test('deleting current administration clears community FK', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->delete("/administrations/{$administration->id}");

    expect($community->fresh()->current_administration_id)->toBeNull();
});

test('deleting administration also removes its members', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    // Has at least the creator as president
    expect($administration->members()->count())->toBeGreaterThan(0);

    $this->actingAs($user)
        ->delete("/administrations/{$administration->id}");

    $this->assertDatabaseMissing('administration_members', [
        'administration_id' => $administration->id,
    ]);
});

test('non-authorized user cannot delete an administration', function () {
    $creator = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($creator, ['name' => 'Test']);
    $administration = $community->currentAdministration;

    $regularUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->delete("/administrations/{$administration->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Member assignment — flat routes
|--------------------------------------------------------------------------
*/

test('can assign member via flat route', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $member = User::factory()->create();
    $community->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $position = $community->positions()->where('name', 'Secretary')->first();
    $administration = $community->currentAdministration;

    $this->actingAs($user)
        ->post("/administrations/{$administration->id}/members", [
            'user_id' => $member->id,
            'position_id' => $position->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('administration_members', [
        'administration_id' => $administration->id,
        'user_id' => $member->id,
        'position_id' => $position->id,
    ]);
});

test('can remove member via flat route', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);
    $member = User::factory()->create();
    $community->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $position = $community->positions()->where('name', 'Secretary')->first();
    $administration = $community->currentAdministration;
    $administration->members()->create(['user_id' => $member->id, 'position_id' => $position->id]);

    $this->actingAs($user)
        ->delete("/administrations/{$administration->id}/members/{$member->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('administration_members', [
        'administration_id' => $administration->id,
        'user_id' => $member->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Administrations — ended_at required on creation
|--------------------------------------------------------------------------
*/

test('creating administration without ended_at returns validation error', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->post('/administrations?community='.$community->id, [
            'started_at' => now()->addYear()->toDateString(),
        ])
        ->assertSessionHasErrors('ended_at');
});

test('creating administration with both started_at and ended_at succeeds', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $startDate = now()->addYear()->toDateString();
    $endDate = now()->addYears(2)->toDateString();

    $this->actingAs($user)
        ->post('/administrations?community='.$community->id, [
            'started_at' => $startDate,
            'ended_at' => $endDate,
        ])
        ->assertRedirect();

    $newAdmin = $community->fresh()->administrations()->latest('id')->first();
    expect($newAdmin->started_at->toDateString())->toBe($startDate);
    expect($newAdmin->ended_at->toDateString())->toBe($endDate);
});

test('creating administration with ended_at before started_at returns validation error', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = app(CreateCommunity::class)->handle($user, ['name' => 'Test']);

    $this->actingAs($user)
        ->post('/administrations?community='.$community->id, [
            'started_at' => '2026-01-01',
            'ended_at' => '2025-01-01',
        ])
        ->assertSessionHasErrors('ended_at');
});
