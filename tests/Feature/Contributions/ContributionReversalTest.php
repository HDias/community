<?php

use App\Actions\Contributions\CalculateDefaultStreaks;
use App\Actions\Contributions\GenerateContributionReports;
use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\Position;
use App\Models\User;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia as Assert;

function attachReversalMember(Community $community, User $user, ?CarbonInterface $joinedAt = null): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => $joinedAt ?? now(),
    ]);
}

function attachReversalExecutive(Community $community, User $user): void
{
    attachReversalMember($community, $user);

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

function attachReversalNonExecutive(Community $community, User $user): void
{
    attachReversalMember($community, $user);

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

function makeMonthlyContribution(
    Community $community,
    User $member,
    User $registrar,
    ?CarbonInterface $month = null,
): Contribution {
    $month = ($month ?? now())->copy()->startOfMonth();

    return $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => $month,
        'monthly_key' => $month,
        'amount' => 50,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Monthly,
    ]);
}

test('admin can reverse a monthly payment with an audit reason', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), [
            'reversal_reason' => 'Payment registered by mistake.',
        ])
        ->assertRedirect(route('contributions.index', [
            'community' => $community->id,
            'month' => $contribution->reference_month->format('Y-m'),
        ]));

    $contribution->refresh();

    expect($contribution->reversed_at)->not->toBeNull()
        ->and($contribution->reversed_by)->toBe($admin->id)
        ->and($contribution->reversal_reason)->toBe('Payment registered by mistake.')
        ->and($contribution->monthly_key)->toBeNull();
});

test('executive can reverse a payment in their community', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachReversalExecutive($community, $executive);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $executive);

    $this->actingAs($executive)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Duplicate entry.'])
        ->assertRedirect();

    expect($contribution->fresh()->reversed_at)->not->toBeNull();
});

test('non-executive position holder cannot reverse a payment', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachReversalNonExecutive($community, $nonExecutive);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Trying to reverse.'])
        ->assertForbidden();

    expect($contribution->fresh()->reversed_at)->toBeNull();
});

test('plain member cannot reverse a payment', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachReversalMember($community, $plainMember);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $plainMember);

    $this->actingAs($plainMember)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Trying to reverse.'])
        ->assertForbidden();
});

test('cross-community executive cannot reverse a payment', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachReversalExecutive(Community::factory()->create(), $outsider);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $outsider);

    $this->actingAs($outsider)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Trying to reverse.'])
        ->assertForbidden();
});

test('a contribution from another community cannot be reversed through a mismatched community', function () {
    $community = Community::factory()->create();
    $otherCommunity = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($otherCommunity, $member);
    $contribution = makeMonthlyContribution($otherCommunity, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Wrong community.'])
        ->assertForbidden();

    expect($contribution->fresh()->reversed_at)->toBeNull();
});

test('a reversal reason is required', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => ''])
        ->assertSessionHasErrors('reversal_reason');

    expect($contribution->fresh()->reversed_at)->toBeNull();
});

test('a reversal reason must have at least three characters', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'no'])
        ->assertSessionHasErrors('reversal_reason');

    expect($contribution->fresh()->reversed_at)->toBeNull();
});

test('a payment cannot be reversed twice', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'First reversal.'])
        ->assertRedirect();

    $firstReversedAt = $contribution->fresh()->reversed_at;

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Second reversal.'])
        ->assertSessionHasErrors('reversal_reason');

    expect($contribution->fresh()->reversed_at->equalTo($firstReversedAt))->toBeTrue();
});

test('a donation can be reversed', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = $community->contributions()->create([
        'user_id' => null,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => null,
        'amount' => 100,
        'registered_by' => $admin->id,
        'type' => ContributionType::Donation,
        'donor_type' => 'organization',
        'donor_name' => 'Padaria Central LTDA',
        'donor_document' => '11222333000181',
    ]);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $donation->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Refunded to the donor.'])
        ->assertRedirect();

    $donation->refresh();

    expect($donation->reversed_at)->not->toBeNull()
        ->and($donation->reversed_by)->toBe($admin->id)
        ->and($donation->reversal_reason)->toBe('Refunded to the donor.')
        ->and($donation->monthly_key)->toBeNull();
});

test('the month can be registered again after a reversal', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachReversalMember($community, $member);

    $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => now()->startOfMonth(),
    ]);

    $contribution = makeMonthlyContribution($community, $member, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $contribution->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Wrong amount.'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('contributions.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'reference_month' => now()->startOfMonth()->format('Y-m-d'),
        ])
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    expect($community->contributions()->where('type', ContributionType::Monthly)->count())->toBe(2);
});

test('a reversed payment is no longer shown as paid in the index', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachReversalMember($community, $member);
    $contribution = makeMonthlyContribution($community, $member, $admin);

    $contribution->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $admin->id,
        'reversal_reason' => 'Refunded.',
        'monthly_key' => null,
    ])->save();

    $this->actingAs($admin)
        ->get(route('contributions.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.data.0.name', 'Ana Silva')
            ->where('members.data.0.paid', false)
            ->where('members.data.0.contribution_id', null)
        );
});

test('a reversed month counts as owed again in the default streak', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $referenceMonth = now()->startOfMonth();
    $member = User::factory()->create();
    attachReversalMember($community, $member, $referenceMonth);
    $contribution = makeMonthlyContribution($community, $member, $admin, $referenceMonth);

    $contribution->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $admin->id,
        'reversal_reason' => 'Refunded.',
        'monthly_key' => null,
    ])->save();

    $streaks = app(CalculateDefaultStreaks::class)->handle($community, $referenceMonth);

    expect($streaks->firstWhere('user_id', $member->id)['months_owed'])->toBe(1);
});

test('a reversed payment is excluded from the generated report totals', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $referenceMonth = now()->startOfMonth();

    $paidMember = User::factory()->create();
    $reversedMember = User::factory()->create();
    attachReversalMember($community, $paidMember, $referenceMonth);
    attachReversalMember($community, $reversedMember, $referenceMonth);

    makeMonthlyContribution($community, $paidMember, $admin, $referenceMonth);
    $reversed = makeMonthlyContribution($community, $reversedMember, $admin, $referenceMonth);

    $reversed->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $admin->id,
        'reversal_reason' => 'Refunded.',
        'monthly_key' => null,
    ])->save();

    $report = app(GenerateContributionReports::class)->handle($community, $referenceMonth);

    expect($report->total_paid)->toBe(1)
        ->and((float) $report->total_collected)->toBe(50.0)
        ->and($report->total_defaulting)->toBe(1);
});
