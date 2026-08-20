<?php

use App\Actions\Members\RegisterMember;
use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\Community;
use App\Models\Position;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\MemberRegistered;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('register member creates user with profile and attaches to community', function () {
    Notification::fake();

    $community = Community::factory()->create();
    $action = new RegisterMember;

    $user = $action->handle($community, [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'cpf' => '52998224725',
        'birth_date' => '1990-01-15',
        'phone' => '11999999999',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->profile)->not->toBeNull()
        ->and($user->profile->cpf)->toBe('52998224725')
        ->and($user->profile->birth_date->format('Y-m-d'))->toBe('1990-01-15')
        ->and($user->profile->phone)->toBe('11999999999')
        ->and($community->members()->where('users.id', $user->id)->exists())->toBeTrue()
        ->and($community->members()->where('users.id', $user->id)->first()->pivot->role)->toBe(CommunityRole::Member->value);

    Notification::assertSentTo($user, MemberRegistered::class);
});

test('register member sends notification with community and password', function () {
    Notification::fake();

    $community = Community::factory()->create();
    $action = new RegisterMember;

    $action->handle($community, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'cpf' => '52998224725',
        'birth_date' => '1985-06-20',
    ]);

    Notification::assertSentTo(
        User::where('email', 'jane@example.com')->first(),
        MemberRegistered::class,
        function (MemberRegistered $notification) use ($community) {
            return $notification->community->is($community)
                && strlen($notification->password) === 12;
        }
    );
});

test('admin can register a member via controller', function () {
    Notification::fake();

    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('members.store', ['community' => $community->id]), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertRedirect();

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->profile)->not->toBeNull()
        ->and($user->profile->cpf)->toBe('52998224725')
        ->and($community->members()->where('users.id', $user->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, MemberRegistered::class);
});

test('duplicate cpf is rejected', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    Profile::factory()->create(['cpf' => '52998224725']);

    $this->actingAs($admin)
        ->post(route('members.store', ['community' => $community->id]), [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertSessionHasErrors('cpf');
});

test('phone is stored as digits only when registering a member', function () {
    Notification::fake();

    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('members.store', ['community' => $community->id]), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'cpf' => '529.982.247-25',
            'birth_date' => '1990-01-15',
            'phone' => '(11) 98765-4321',
        ])
        ->assertRedirect();

    expect(User::where('email', 'john@example.com')->first()->profile->phone)
        ->toBe('11987654321');
});

test('phone is stored as digits only when updating a member', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $member = User::factory()->create();
    Profile::factory()->create(['user_id' => $member->id]);
    $community->members()->attach($member, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($admin)
        ->put(route('members.update', ['member' => $member->id, 'community' => $community->id]), [
            'name' => $member->name,
            'email' => $member->email,
            'cpf' => '529.982.247-25',
            'birth_date' => '1990-01-15',
            'phone' => '(11) 3456-7890',
        ])
        ->assertRedirect();

    expect($member->refresh()->profile->phone)->toBe('1134567890');
});

/**
 * `members/create` reads nothing but `community`, and it builds every URL on the
 * page from the id -- see `tests/js/pages/members-create.test.tsx`.
 */
test('member create page exposes only the community it is scoped to', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('members.create', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/create')
            ->has('community', fn (Assert $scope) => $scope
                ->where('id', $community->id)
                ->where('name', $community->name)
                ->where('slug', $community->slug)
            )
        );
});

test('member edit page exposes birth date in Y-m-d for the date input', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $member = User::factory()->create();
    Profile::factory()->create([
        'user_id' => $member->id,
        'birth_date' => '1972-06-25',
    ]);
    $community->members()->attach($member, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('members.edit', ['member' => $member->id, 'community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/edit')
            ->where('member.profile.birth_date', '1972-06-25')
        );
});

/**
 * The CPF and phone leave the server as digits only. `CpfInput` and `PhoneInput`
 * apply the mask on render, which is asserted from the other side in
 * `tests/js/pages/members-edit.test.tsx`.
 */
test('member edit page sends the CPF and phone unformatted', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $member = User::factory()->create();
    Profile::factory()->create([
        'user_id' => $member->id,
        'cpf' => '52998224725',
        'phone' => '11987654321',
    ]);
    $community->members()->attach($member, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('members.edit', ['member' => $member->id, 'community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/edit')
            ->where('member.profile.cpf', '52998224725')
            ->where('member.profile.phone', '11987654321')
        );
});

test('non-admin cannot register members', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create();
    $community->members()->attach($member, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->post(route('members.store', ['community' => $community->id]), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'cpf' => '52998224725',
            'birth_date' => '1990-01-15',
        ])
        ->assertForbidden();
});

/**
 * Pins the row shape `members/index` renders, mocked in
 * `tests/js/pages/members-index.test.tsx`. A renamed or dropped key breaks here.
 */
test('member index lists community members', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $member = User::factory()->create();
    Profile::factory()->create(['user_id' => $member->id]);
    $community->members()->attach($member, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('members.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->has('members.data', 1)
            ->where('members.data.0.id', $member->id)
            ->where('members.data.0.name', $member->name)
            ->where('members.data.0.email', $member->email)
            ->where('members.data.0.pivot.role', CommunityRole::Member->value)
            ->has('members.data.0.profile.cpf')
            ->has('members.data.0.profile.phone')
            ->has('members.data.0.profile.address_city')
        );
});

test('member index exposes the position held in the current administration', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $administration = Administration::factory()->create(['community_id' => $community->id]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Treasurer',
    ]);

    $officer = User::factory()->create(['name' => 'Alice']);
    $plainMember = User::factory()->create(['name' => 'Bob']);

    foreach ([$officer, $plainMember] as $user) {
        $community->members()->attach($user, [
            'role' => CommunityRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $administration->members()->create([
        'user_id' => $officer->id,
        'position_id' => $position->id,
    ]);

    $this->actingAs($admin)
        ->get(route('members.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/index')
            ->where('members.data.0.position', 'Treasurer')
            ->where('members.data.1.position', null)
        );
});
