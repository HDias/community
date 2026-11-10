<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createDashboardContribution(Community $community, User $member, User $registrar, array $attributes = []): Contribution
{
    return $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => now()->startOfMonth(),
        'amount' => 50,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Monthly,
        ...$attributes,
    ]);
}

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
            ->where('myContributions', null)
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

test('dashboard exposes the member contribution summary and own history', function () {
    $this->travelTo('2026-06-15 12:00:00');

    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subYear(),
    ]);
    $member->switchCommunity($community);

    $registrar = User::factory()->create(['is_admin' => true, 'name' => 'Treasurer']);

    createDashboardContribution($community, $member, $registrar, [
        'reference_month' => '2026-03-01',
        'monthly_key' => '2026-03-01',
        'amount' => 50,
    ]);
    createDashboardContribution($community, $member, $registrar, [
        'reference_month' => '2026-06-01',
        'monthly_key' => '2026-06-01',
        'amount' => 50,
    ]);
    createDashboardContribution($community, $member, $registrar, [
        'reference_month' => '2026-06-01',
        'monthly_key' => null,
        'amount' => 30,
        'type' => ContributionType::Donation,
        'donor_type' => 'member',
    ]);

    $reversed = createDashboardContribution($community, $member, $registrar, [
        'reference_month' => '2026-05-01',
        'monthly_key' => null,
        'amount' => 20,
    ]);
    $reversed->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $registrar->id,
        'reversal_reason' => 'Mistake.',
    ])->save();

    $otherMember = User::factory()->create();
    $community->members()->attach($otherMember->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    createDashboardContribution($community, $otherMember, $registrar, [
        'reference_month' => '2026-06-01',
        'monthly_key' => '2026-06-01',
        'amount' => 99,
    ]);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('myContributions.totalPaidThisYear', '130.00')
            ->where('myContributions.currentMonthStatus', 'paid')
            ->where('myContributions.nextDueMonth', '2026-07')
            ->has('myContributions.history', 4)
            ->where('myContributions.history.0.type', 'donation')
            ->where('myContributions.history.0.amount', '30.00')
            ->where('myContributions.history.0.status', 'active')
            ->where('myContributions.history.1.type', 'monthly')
            ->where('myContributions.history.1.status', 'active')
            ->where('myContributions.history.2.status', 'reversed')
            ->where('myContributions.history.2.reversal_reason', 'Mistake.')
            ->where('myContributions.history.3.reference_month', '2026-03')
        );
});

test('dashboard reports the current month as pending when its payment was reversed', function () {
    $this->travelTo('2026-06-15 12:00:00');

    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subYear(),
    ]);
    $member->switchCommunity($community);

    $registrar = User::factory()->create(['is_admin' => true]);

    $contribution = createDashboardContribution($community, $member, $registrar, [
        'reference_month' => '2026-06-01',
        'monthly_key' => null,
        'amount' => 50,
    ]);
    $contribution->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $registrar->id,
        'reversal_reason' => 'Mistake.',
    ])->save();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('myContributions.totalPaidThisYear', '0.00')
            ->where('myContributions.currentMonthStatus', 'pending')
            ->where('myContributions.nextDueMonth', '2026-06')
            ->has('myContributions.history', 1)
        );
});

test('dashboard reports no next due month when the next twelve months are paid', function () {
    $this->travelTo('2026-06-15 12:00:00');

    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subYear(),
    ]);
    $member->switchCommunity($community);

    $registrar = User::factory()->create(['is_admin' => true]);

    foreach (range(0, 11) as $offset) {
        $month = now()->addMonths($offset)->startOfMonth();

        createDashboardContribution($community, $member, $registrar, [
            'reference_month' => $month,
            'monthly_key' => $month,
        ]);
    }

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('myContributions.currentMonthStatus', 'paid')
            ->where('myContributions.nextDueMonth', null)
        );
});

test('dashboard contribution history is scoped to the current community and member', function () {
    $this->travelTo('2026-06-15 12:00:00');

    $community = Community::factory()->create();
    $otherCommunity = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subYear(),
    ]);
    $otherCommunity->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now()->subYear(),
    ]);
    $member->switchCommunity($community);

    $registrar = User::factory()->create(['is_admin' => true]);

    createDashboardContribution($community, $member, $registrar, ['amount' => 50]);
    createDashboardContribution($otherCommunity, $member, $registrar, ['amount' => 999]);
    $community->contributions()->create([
        'user_id' => null,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => null,
        'amount' => 500,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Donation,
        'donor_type' => 'individual',
        'donor_name' => 'Maria Souza',
    ]);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('myContributions.totalPaidThisYear', '50.00')
            ->has('myContributions.history', 1)
            ->where('myContributions.history.0.amount', '50.00')
        );
});

test('dashboard exposes an empty contribution summary for a member without contributions', function () {
    $this->travelTo('2026-06-15 12:00:00');

    $community = Community::factory()->create();
    $member = User::factory()->create(['is_admin' => false]);
    $community->members()->attach($member->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
    $member->switchCommunity($community);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('myContributions.totalPaidThisYear', '0.00')
            ->where('myContributions.currentMonthStatus', 'pending')
            ->where('myContributions.nextDueMonth', '2026-06')
            ->has('myContributions.history', 0)
        );
});
