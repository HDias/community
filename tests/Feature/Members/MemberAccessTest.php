<?php

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->has('communities', 1)
            ->where('communities.0', ['id' => $community->id, 'name' => $community->name])
            ->where('community', [
                'id' => $community->id,
                'name' => $community->name,
                'slug' => $community->slug,
            ])
            ->has('members.data', 1)
            ->has('members.data.0', fn (Assert $row) => $row
                ->where('id', $executive->id)
                ->where('name', $executive->name)
                ->where('email', $executive->email)
                ->has('position')
                ->has('profile')
                ->has('pivot.role')
                ->has('pivot.joined_at')
                ->etc()
            )
            ->has('members.current_page')
            ->has('members.last_page')
            ->has('members.per_page')
            ->has('members.total')
        );
});

test('executive member is redirected to their only manageable community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('members.index'))
        ->assertRedirect('/members?community='.$community->id);
});

/**
 * The empty `members` array here is the shape `members/index` guards with
 * `Array.isArray`, covered from the client side in
 * `tests/js/pages/members-index.test.tsx`.
 */
test('admin without any community sees the empty members index', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->has('communities', 0)
            ->where('community', null)
            ->where('members', [])
        );
});

test('executive member can access the edit form for a member of their community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachExecutive($community, $executive);

    $member = User::factory()->create();
    attachPlainMember($community, $member);

    $this->actingAs($executive)
        ->get(route('members.edit', ['member' => $member->id, 'community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/edit')
            ->where('member.id', $member->id)
            ->where('member.name', $member->name)
            ->where('member.email', $member->email)
            ->where('member.profile', null)
            ->where('community', [
                'id' => $community->id,
                'name' => $community->name,
                'slug' => $community->slug,
            ])
        );
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
