<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function attachContributionReportMember(Community $community, User $user, ?string $joinedAt = null): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => $joinedAt ?? now(),
    ]);
}

function attachContributionReportExecutive(Community $community, User $user): void
{
    attachContributionReportMember($community, $user);

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

function attachContributionReportNonExecutive(Community $community, User $user): void
{
    attachContributionReportMember($community, $user);

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

test('admin can view the defaults report', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('contributions/report'));
});

test('executive can view the defaults report', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachContributionReportExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertOk();
});

test('non-executive position holder cannot view the defaults report', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachContributionReportNonExecutive($community, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('plain member cannot view the defaults report', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachContributionReportMember($community, $plainMember);

    $this->actingAs($plainMember)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('cross-community executive cannot view another community defaults report', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachContributionReportExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('a member with 3 or more consecutive unpaid months is flagged overdue_3plus', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachContributionReportMember($community, $member, now()->subMonths(5)->startOfMonth()->toDateString());

    $this->actingAs($admin)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaulters.0.user_id', $member->id)
            ->where('defaulters.0.overdue_3plus', true)
        );
});

test('a member with 1-2 unpaid months is not flagged overdue_3plus', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachContributionReportMember($community, $member, now()->subMonth()->startOfMonth()->toDateString());

    $this->actingAs($admin)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaulters.0.user_id', $member->id)
            ->where('defaulters.0.overdue_3plus', false)
        );
});

test('a member who joined mid-history is not counted as owing months before they joined', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachContributionReportMember($community, $member, now()->startOfMonth()->toDateString());

    $this->actingAs($admin)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaulters.0.months_owed', 1)
        );
});

test('a fully paid member does not appear in the defaulters list', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachContributionReportMember($community, $member, now()->startOfMonth()->toDateString());

    $referenceMonth = now()->startOfMonth();

    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.report.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page->has('defaulters', 0));
});
