<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;

function attachRegisterDonationMember(Community $community, User $user): void
{
    $community->members()->attach($user->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);
}

function attachRegisterDonationExecutive(Community $community, User $user): void
{
    attachRegisterDonationMember($community, $user);

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

function attachRegisterDonationNonExecutive(Community $community, User $user): void
{
    attachRegisterDonationMember($community, $user);

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

test('admin can register an anonymous donation', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'amount' => 200,
            'notes' => 'Collected at the meeting.',
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    $donation = $community->contributions()->where('type', ContributionType::Donation)->first();

    expect($donation)->not->toBeNull()
        ->and($donation->user_id)->toBeNull()
        ->and($donation->monthly_key)->toBeNull()
        ->and($donation->registered_by)->toBe($admin->id)
        ->and($donation->notes)->toBe('Collected at the meeting.');
});

test('executive can register an attributed donation', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationExecutive($community, $executive);
    $member = User::factory()->create();
    attachRegisterDonationMember($community, $member);

    $this->actingAs($executive)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'amount' => 100,
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    $donation = $community->contributions()->where('type', ContributionType::Donation)->first();

    expect($donation->user_id)->toBe($member->id)
        ->and($donation->monthly_key)->toBeNull();
});

test('non-executive position holder cannot register a donation', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationNonExecutive($community, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('plain member cannot register a donation', function () {
    $community = Community::factory()->create();
    $plainMember = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationMember($community, $plainMember);

    $this->actingAs($plainMember)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('cross-community executive cannot register a donation in another community', function () {
    $community = Community::factory()->create();
    $outsider = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationExecutive(Community::factory()->create(), $outsider);

    $this->actingAs($outsider)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('multiple donations in the same month for the same member are allowed', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachRegisterDonationMember($community, $member);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'amount' => 50,
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'user_id' => $member->id,
            'amount' => 75,
        ])
        ->assertRedirect('/contributions?community='.$community->id);

    expect($community->contributions()->where('user_id', $member->id)->count())->toBe(2);
});

test('attributed user_id not belonging to the resolved community fails validation', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $outsider = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'user_id' => $outsider->id,
            'amount' => 50,
        ])
        ->assertSessionHasErrors('user_id');
});
