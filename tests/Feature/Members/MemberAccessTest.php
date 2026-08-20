<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;

/**
 * Attach a user to a community as a plain member.
 */
function attachPlainMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

/**
 * Attach a user to a community holding a position with admin access.
 */
function attachExecutive(Community $community, User $user): void
{
    attachPlainMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'has_admin_access' => true,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

test('plain member cannot access the members index of their community', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    attachPlainMember($community, $member);

    $this->actingAs($member)
        ->get(route('members.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('plain member cannot access the members index without a community', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    attachPlainMember($community, $member);

    $this->actingAs($member)
        ->get(route('members.index'))
        ->assertForbidden();
});

test('member of another community cannot access the members index', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->get(route('members.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('executive member can access the members index', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('members.index', ['community' => $community->id]))
        ->assertOk();
});

test('executive member is redirected to their only manageable community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('members.index'))
        ->assertRedirect('/members?community='.$community->id);
});

test('admin without any community sees the empty members index', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('members.index'))
        ->assertOk();
});

test('executive member can access the edit form for a member of their community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $member = User::factory()->create();
    attachPlainMember($community, $member);

    $this->actingAs($executive)
        ->get(route('members.edit', ['member' => $member->id, 'community' => $community->id]))
        ->assertOk();
});

test('plain member cannot access the edit form', function () {
    $community = Community::factory()->create();
    $plain = User::factory()->create(['is_admin' => false]);
    attachPlainMember($community, $plain);

    $member = User::factory()->create();
    attachPlainMember($community, $member);

    $this->actingAs($plain)
        ->get(route('members.edit', ['member' => $member->id, 'community' => $community->id]))
        ->assertForbidden();
});

test('executive cannot edit a member from another community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $outsiderMember = User::factory()->create();
    attachPlainMember(Community::factory()->create(), $outsiderMember);

    $this->actingAs($executive)
        ->get(route('members.edit', ['member' => $outsiderMember->id, 'community' => $community->id]))
        ->assertForbidden();
});

test('executive member can update a member of their community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $member = User::factory()->create(['name' => 'Old Name']);
    attachPlainMember($community, $member);

    $this->actingAs($executive)
        ->put(route('members.update', ['member' => $member->id, 'community' => $community->id]), [
            'name' => 'New Name',
            'email' => $member->email,
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertRedirect(route('members.index', ['community' => $community->id]));

    expect($member->refresh()->name)->toBe('New Name');
});

test('plain member cannot update a member', function () {
    $community = Community::factory()->create();
    $plain = User::factory()->create(['is_admin' => false]);
    attachPlainMember($community, $plain);

    $member = User::factory()->create(['name' => 'Old Name']);
    attachPlainMember($community, $member);

    $this->actingAs($plain)
        ->put(route('members.update', ['member' => $member->id, 'community' => $community->id]), [
            'name' => 'New Name',
            'email' => $member->email,
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertForbidden();

    expect($member->refresh()->name)->toBe('Old Name');
});

test('executive cannot update a member from another community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $outsiderMember = User::factory()->create(['name' => 'Old Name']);
    attachPlainMember(Community::factory()->create(), $outsiderMember);

    $this->actingAs($executive)
        ->put(route('members.update', ['member' => $outsiderMember->id, 'community' => $community->id]), [
            'name' => 'New Name',
            'email' => $outsiderMember->email,
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertForbidden();

    expect($outsiderMember->refresh()->name)->toBe('Old Name');
});
