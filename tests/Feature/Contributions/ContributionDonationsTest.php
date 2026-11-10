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

function attachDonationMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

function attachDonationExecutive(Community $community, User $user): void
{
    attachDonationMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Treasurer',
        'has_admin_access' => true,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

function attachDonationNonExecutive(Community $community, User $user): void
{
    attachDonationMember($community, $user);

    $administration = $community->currentAdministration ?? Administration::factory()->create([
        'community_id' => $community->id,
    ]);
    $community->update(['current_administration_id' => $administration->id]);

    $position = Position::factory()->create([
        'community_id' => $community->id,
        'name' => 'Coordinator',
        'has_admin_access' => false,
    ]);

    AdministrationMember::factory()->create([
        'administration_id' => $administration->id,
        'user_id' => $user->id,
        'position_id' => $position->id,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function makeDonation(Community $community, User $registrar, array $overrides = []): Contribution
{
    return $community->contributions()->create([
        'user_id' => null,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => null,
        'amount' => 100,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Donation,
        'donor_type' => 'individual',
        'donor_name' => 'Maria Souza',
        'donor_document' => '52998224725',
        ...$overrides,
    ]);
}

test('admin can view the donations index', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/donations/index')
            ->where('community.id', $community->id)
        );
});

test('executive can view the donations index', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachDonationExecutive($community, $executive);

    $this->actingAs($executive)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertOk();
});

test('non-executive position holder cannot view the donations index', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachDonationNonExecutive($community, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('plain member cannot view the donations index', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachDonationMember($community, $plainMember);

    $this->actingAs($plainMember)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('cross-community executive cannot view another community donations index', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachDonationExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertForbidden();
});

test('a user managing a single community is redirected to its donations index', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index'))
        ->assertRedirect('/contributions/donations?community='.$community->id);
});

test('a user managing multiple communities sees the donations community picker', function () {
    Community::factory()->count(2)->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/donations/index')
            ->where('community', null)
            ->has('communities', 2)
        );
});

test('the donations list only contains donations', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachDonationMember($community, $member);

    $community->contributions()->create([
        'user_id' => $member->id,
        'reference_month' => now()->startOfMonth(),
        'monthly_key' => now()->startOfMonth(),
        'amount' => 50,
        'registered_by' => $admin->id,
        'type' => ContributionType::Monthly,
    ]);

    makeDonation($community, $admin);
    makeDonation($community, $admin, ['donor_type' => 'organization', 'donor_name' => 'Padaria Central LTDA', 'donor_document' => '11222333000181']);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 2)
            ->where('donations.total', 2)
        );
});

test('a donation row exposes its registration metadata', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Treasurer']);
    makeDonation($community, $admin);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('donations.data.0.donor_name', 'Maria Souza')
            ->where('donations.data.0.donor_document', '52998224725')
            ->where('donations.data.0.status', 'active')
            ->where('donations.data.0.registered_by_name', 'Treasurer')
            ->whereNot('donations.data.0.registered_at', null)
            ->where('donations.data.0.reversed_at', null)
            ->where('donations.data.0.reversal_reason', null)
        );
});

test('a reversed donation is listed with the reversed status', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Treasurer']);
    $reverser = User::factory()->create(['is_admin' => true, 'name' => 'Auditor']);
    $donation = makeDonation($community, $admin);

    $donation->forceFill([
        'reversed_at' => now(),
        'reversed_by' => $reverser->id,
        'reversal_reason' => 'Refunded.',
    ])->save();

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('donations.data.0.status', 'reversed')
            ->where('donations.data.0.reversed_by_name', 'Auditor')
            ->where('donations.data.0.reversal_reason', 'Refunded.')
            ->whereNot('donations.data.0.reversed_at', null)
        );
});

test('the month filter only returns donations from that month', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $previousMonth = now()->subMonth()->startOfMonth();

    makeDonation($community, $admin);
    makeDonation($community, $admin, [
        'reference_month' => $previousMonth,
        'donor_name' => 'Ana Silva',
    ]);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'month' => $previousMonth->format('Y-m'),
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 1)
            ->where('donations.data.0.donor_name', 'Ana Silva')
            ->where('donations.data.0.reference_month', $previousMonth->format('Y-m'))
            ->where('referenceMonth', $previousMonth->format('Y-m'))
        );
});

test('without a month filter all donations are returned', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    makeDonation($community, $admin);
    makeDonation($community, $admin, ['reference_month' => now()->subMonth()->startOfMonth()]);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 2)
            ->where('referenceMonth', null)
        );
});

test('donations can be searched by member name', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Bruno Costa']);
    attachDonationMember($community, $member);

    makeDonation($community, $admin, [
        'user_id' => $member->id,
        'donor_type' => 'member',
        'donor_name' => null,
        'donor_document' => null,
    ]);
    makeDonation($community, $admin);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'search' => 'Bruno',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 1)
            ->where('donations.data.0.member_name', 'Bruno Costa')
        );
});

test('donations can be searched by donor name', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    makeDonation($community, $admin);
    makeDonation($community, $admin, ['donor_name' => 'Padaria Central LTDA']);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'search' => 'Padaria',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 1)
            ->where('donations.data.0.donor_name', 'Padaria Central LTDA')
        );
});

test('donations can be searched by donor document with or without formatting', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    makeDonation($community, $admin);
    makeDonation($community, $admin, ['donor_name' => 'Outro Doador', 'donor_document' => '11222333000181']);

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'search' => '529.982.247-25',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 1)
            ->where('donations.data.0.donor_name', 'Maria Souza')
        );

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'search' => '11.222.333/0001-81',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 1)
            ->where('donations.data.0.donor_name', 'Outro Doador')
        );
});

test('the donations list is paginated', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (range(1, 55) as $index) {
        makeDonation($community, $admin, ['donor_name' => "Doador {$index}"]);
    }

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 50)
            ->where('donations.total', 55)
            ->where('donations.per_page', 50)
            ->where('donations.current_page', 1)
            ->where('donations.last_page', 2)
        );

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', [
            'community' => $community->id,
            'page' => 2,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('donations.data', 5)
            ->where('donations.current_page', 2)
        );
});

test('admin can reverse a donation with an audit reason', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeDonation($community, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $donation->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Refunded to the donor.'])
        ->assertRedirect();

    $donation->refresh();

    expect($donation->reversed_at)->not->toBeNull()
        ->and($donation->reversed_by)->toBe($admin->id)
        ->and($donation->reversal_reason)->toBe('Refunded to the donor.');

    $this->actingAs($admin)
        ->get(route('contributions.donations.index', ['community' => $community->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('donations.data.0.status', 'reversed')
        );
});

test('a donation reversal reason is required', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeDonation($community, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $donation->id,
            'community' => $community->id,
        ]), ['reversal_reason' => ''])
        ->assertSessionHasErrors('reversal_reason');

    expect($donation->fresh()->reversed_at)->toBeNull();
});

test('a donation cannot be reversed twice', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $donation = makeDonation($community, $admin);

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $donation->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'First reversal.'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('contributions.reverse', [
            'contribution' => $donation->id,
            'community' => $community->id,
        ]), ['reversal_reason' => 'Second reversal.'])
        ->assertSessionHasErrors('reversal_reason');
});
