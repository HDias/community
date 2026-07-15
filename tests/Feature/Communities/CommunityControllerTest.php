<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('communities index shows onboarding for admin users with no communities', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get(route('communities.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('communities/onboarding'));
});

test('communities index shows card grid for admin users with communities', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create(['created_by' => $user->id]);
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $response = $this->actingAs($user)->get(route('communities.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('communities/index')
        ->has('communities', 1)
        ->where('communities.0.name', $community->name)
        ->where('communities.0.role', 'admin')
        ->where('communities.0.is_current', true));
});

test('non-admin cannot create community', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->post(route('communities.store'), [
        'name' => 'Forbidden Community',
    ])->assertForbidden();

    $this->assertDatabaseMissing('communities', ['name' => 'Forbidden Community']);
});

test('store creates community and attaches creator as president', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->post(route('communities.store'), [
        'name' => 'Test Community',
        'description' => 'A test description',
        'city' => 'São Paulo',
        'state' => 'SP',
    ]);

    $response->assertRedirect(route('communities.index'));

    $community = Community::where('name', 'Test Community')->first();
    expect($community)->not->toBeNull();
    expect($community->created_by)->toBe($user->id);
    expect($community->members()->where('user_id', $user->id)->first()->pivot->role)->toBe('president');
    expect($user->fresh()->current_community_id)->toBe($community->id);
});

test('store validates required fields', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->post(route('communities.store'), []);

    $response->assertSessionHasErrors('name');
});

test('only executive members or admin can update community', function () {
    $community = Community::factory()->create();
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'President']);
    $executive = User::factory()->create();
    $community->members()->attach($executive->id, ['role' => 'member', 'joined_at' => now()]);
    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $executive->id,
        'position_id' => $position->id,
    ]);

    $other = User::factory()->create();

    $this->actingAs($other)->put(route('communities.update', $community), [
        'name' => 'Hacked',
    ])->assertForbidden();

    $this->actingAs($executive)->put(route('communities.update', $community), [
        'name' => 'Updated Name',
    ])->assertRedirect();

    expect($community->fresh()->name)->toBe('Updated Name');
});

test('only admin can delete community', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $other->id]);

    $this->actingAs($other)->delete(route('communities.destroy', $community))
        ->assertForbidden();

    $this->actingAs($admin)->delete(route('communities.destroy', $community))
        ->assertRedirect();

    expect(Community::find($community->id))->toBeNull();
    expect(Community::withTrashed()->find($community->id))->not->toBeNull();
});

test('switch community updates current_community_id', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();
    $community->members()->attach($user->id, ['role' => CommunityRole::Member->value]);

    $response = $this->actingAs($user)
        ->from(route('communities.index'))
        ->post(route('communities.switch', $community));

    $response->assertRedirect(route('communities.index'));
    expect($user->fresh()->current_community_id)->toBe($community->id);
});

test('switch community fails for non-member', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();

    $this->actingAs($user)->post(route('communities.switch', $community))
        ->assertForbidden();
});
