<?php

use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Enums\DonorType;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

test('admin can view the donation registration form', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create(['name' => 'Ana Silva']);
    attachRegisterDonationMember($community, $member);

    $this->actingAs($admin)
        ->get(route('contributions.donations.create', ['community' => $community->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contributions/donations/create')
            ->where('community.id', $community->id)
            ->has('members', 1)
        );
});

test('admin can register a donation from an individual donor', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Individual->value,
            'donor_name' => 'Maria Souza',
            'donor_document' => '529.982.247-25',
            'amount' => 200,
            'notes' => 'Collected at the meeting.',
        ])
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    $donation = $community->contributions()->where('type', ContributionType::Donation)->first();

    expect($donation)->not->toBeNull()
        ->and($donation->user_id)->toBeNull()
        ->and($donation->monthly_key)->toBeNull()
        ->and($donation->donor_type)->toBe(DonorType::Individual)
        ->and($donation->donor_name)->toBe('Maria Souza')
        ->and($donation->donor_document)->toBe('52998224725')
        ->and($donation->registered_by)->toBe($admin->id)
        ->and($donation->notes)->toBe('Collected at the meeting.');
});

test('executive can register a donation attributed to a member', function () {
    $community = Community::factory()->create();
    $executive = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationExecutive($community, $executive);
    $member = User::factory()->create();
    attachRegisterDonationMember($community, $member);

    $this->actingAs($executive)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Member->value,
            'user_id' => $member->id,
            'amount' => 100,
        ])
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    $donation = $community->contributions()->where('type', ContributionType::Donation)->first();

    expect($donation->user_id)->toBe($member->id)
        ->and($donation->donor_type)->toBe(DonorType::Member)
        ->and($donation->donor_name)->toBeNull()
        ->and($donation->monthly_key)->toBeNull();
});

test('admin can register a donation from a legal entity', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Organization->value,
            'donor_name' => 'Padaria Central LTDA',
            'donor_document' => '11.222.333/0001-81',
            'amount' => 500,
        ])
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    $donation = $community->contributions()->where('type', ContributionType::Donation)->first();

    expect($donation->user_id)->toBeNull()
        ->and($donation->donor_type)->toBe(DonorType::Organization)
        ->and($donation->donor_name)->toBe('Padaria Central LTDA')
        ->and($donation->donor_document)->toBe('11222333000181');
});

test('an invalid cpf is rejected for an individual donor', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Individual->value,
            'donor_name' => 'Maria Souza',
            'donor_document' => '123.456.789-00',
            'amount' => 200,
        ])
        ->assertSessionHasErrors('donor_document');

    expect($community->contributions()->count())->toBe(0);
});

test('an invalid cnpj is rejected for a legal entity donor', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Organization->value,
            'donor_name' => 'Padaria Central LTDA',
            'donor_document' => '11.111.111/1111-11',
            'amount' => 500,
        ])
        ->assertSessionHasErrors('donor_document');

    expect($community->contributions()->count())->toBe(0);
});

test('a member donor requires a user id from the community', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Member->value,
            'amount' => 100,
        ])
        ->assertSessionHasErrors('user_id');
});

test('an individual donor requires a name and a document', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Individual->value,
            'amount' => 100,
        ])
        ->assertSessionHasErrors(['donor_name', 'donor_document']);
});

test('non-executive position holder cannot register a donation', function () {
    $community = Community::factory()->create();
    $nonExecutive = User::factory()->create(['is_admin' => false]);
    attachRegisterDonationNonExecutive($community, $nonExecutive);

    $this->actingAs($nonExecutive)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Individual->value,
            'donor_name' => 'Maria Souza',
            'donor_document' => '529.982.247-25',
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
            'donor_type' => DonorType::Individual->value,
            'donor_name' => 'Maria Souza',
            'donor_document' => '529.982.247-25',
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
            'donor_type' => DonorType::Individual->value,
            'donor_name' => 'Maria Souza',
            'donor_document' => '529.982.247-25',
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('multiple donations in the same month for the same member are allowed', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $member = User::factory()->create();
    attachRegisterDonationMember($community, $member);

    $payload = [
        'donor_type' => DonorType::Member->value,
        'user_id' => $member->id,
        'amount' => 50,
    ];

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), $payload)
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            ...$payload,
            'amount' => 75,
        ])
        ->assertRedirect(route('contributions.index', ['community' => $community->id]));

    expect($community->contributions()->where('user_id', $member->id)->count())->toBe(2);
});

test('attributed user_id not belonging to the resolved community fails validation', function () {
    $community = Community::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $outsider = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('contributions.donations.store', ['community' => $community->id]), [
            'donor_type' => DonorType::Member->value,
            'user_id' => $outsider->id,
            'amount' => 50,
        ])
        ->assertSessionHasErrors('user_id');
});
