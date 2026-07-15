<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard returns null community when user has no current community', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('community', null)
            ->where('canManage', false));
});

test('dashboard returns community data for member with current community', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subMonths(3),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('community.name', $community->name)
            ->where('community.slug', $community->slug)
            ->where('canManage', false)
            ->has('memberCount')
            ->has('memberSince')
            ->has('administrationMembers'));
});

test('dashboard returns canManage true for admin user', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $community = Community::factory()->create(['created_by' => $user->id]);
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManage', true));
});

test('dashboard returns canManage true for president user', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManage', true));
});

test('dashboard returns administration members when community has current administration', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $position = Position::factory()->create(['community_id' => $community->id, 'name' => 'President']);
    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $president = User::factory()->create();
    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $president->id,
        'position_id' => $position->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('administrationMembers', 1)
            ->where('administrationMembers.0.name', $president->name)
            ->where('administrationMembers.0.position', 'President'));
});

test('member cannot access positions index', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('positions.index'))
        ->assertForbidden();
});

test('member cannot access administrations index', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('administrations.index'))
        ->assertForbidden();
});

test('member cannot access communities index', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community = Community::factory()->create();
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);

    $this->actingAs($user)
        ->get(route('communities.index'))
        ->assertForbidden();
});

test('dashboard returns user communities for switcher', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $community1 = Community::factory()->create();
    $community2 = Community::factory()->create();
    $community1->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $community2->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community1);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('communities', 2)
            ->where('communities.0.id', $community1->id)
            ->where('communities.0.name', $community1->name));
});
