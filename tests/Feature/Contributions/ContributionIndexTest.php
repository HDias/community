<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachContributionIndexMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

function attachContributionIndexExecutive(Community $community, User $user): void
{
    attachContributionIndexMember($community, $user);

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

function attachContributionIndexNonExecutive(Community $community, User $user): void
{
    attachContributionIndexMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'has_admin_access' => false,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

test('admin can view the contributions index', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/index')
            ->where('community.id', $community->id)
        );
});

test('executive can view the contributions index', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachContributionIndexExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertOk();
});

test('non-executive position holder cannot view the contributions index', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachContributionIndexNonExecutive($community, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('plain member cannot view the contributions index', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachContributionIndexMember($community, $plainMember);

    $this->actingAs($plainMember)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('cross-community executive cannot view another community contributions index', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachContributionIndexExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('a user managing a single community is redirected to its contributions index', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.index'))
        ->assertRedirect('/contributions?community='.$community->id);
});

test('a user managing multiple communities sees the community picker', function () {
    Community::factory()->count(2)->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/index')
            ->where('community', null)
            ->has('communities', 2)
        );
});

test('members are marked as paid when they have a monthly contribution for the reference month', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $paidMember = User::factory()->create(['name' => 'Ana Silva']);
    $pendingMember = User::factory()->create(['name' => 'Bruno Costa']);
    attachContributionIndexMember($community, $paidMember);
    attachContributionIndexMember($community, $pendingMember);

    $referenceMonth = now()->startOfMonth();

    $community->contributions()->create([
        'user_id' => $paidMember->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.0.name', 'Ana Silva')
            ->where('members.0.paid', true)
            ->where('members.1.name', 'Bruno Costa')
            ->where('members.1.paid', false)
        );
});

test('the month query parameter changes the reference month', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachContributionIndexMember($community, $member);

    $previousMonth = now()->subMonth()->startOfMonth();

    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => $previousMonth,
        'monthly_key' => $previousMonth,
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.index', [
            'community' => $community->id,
            'month' => $previousMonth->format('Y-m'),
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('referenceMonth', $previousMonth->format('Y-m'))
            ->where('members.0.paid', true)
        );
});

test('the effective monthly amount for the reference month is exposed', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => now()->startOfMonth(),
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('monthlyAmount', '50.00')
        );
});
