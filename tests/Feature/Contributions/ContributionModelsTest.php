<?php

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\ContributionReport;
use App\Models\ContributionSetting;
use App\Models\User;
use Illuminate\Database\QueryException;

test('contribution setting factory creates a valid row tied to a community', function () {
    $setting = ContributionSetting::factory()->create();

    expect($setting->community)->toBeInstanceOf(Community::class)
        ->and($setting->monthly_amount)->not->toBeNull()
        ->and($setting->effective_from)->not->toBeNull();
});

test('community can accumulate contribution setting history without overwriting prior rows', function () {
    $community = Community::factory()->create();

    $community->contributionSettings()->create([
        'monthly_amount' => 30,
        'effective_from' => now()->subMonths(2)->startOfMonth(),
    ]);
    $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => now()->startOfMonth(),
    ]);

    expect($community->contributionSettings()->count())->toBe(2);
});

test('contribution setting currentFor resolves the latest row effective on or before the reference month', function () {
    $community = Community::factory()->create();

    $community->contributionSettings()->create([
        'monthly_amount' => 30,
        'effective_from' => now()->subMonths(3)->startOfMonth(),
    ]);
    $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => now()->subMonth()->startOfMonth(),
    ]);

    $current = ContributionSetting::currentFor($community, now());
    $past = ContributionSetting::currentFor($community, now()->subMonths(2));

    expect((float) $current->monthly_amount)->toBe(50.0)
        ->and((float) $past->monthly_amount)->toBe(30.0);
});

test('contribution factory creates a monthly payment tied to a community and payer', function () {
    $contribution = Contribution::factory()->create();

    expect($contribution->community)->toBeInstanceOf(Community::class)
        ->and($contribution->payer)->toBeInstanceOf(User::class)
        ->and($contribution->registeredBy)->toBeInstanceOf(User::class)
        ->and($contribution->type)->toBe(ContributionType::Monthly);
});

test('contribution allows a null payer for anonymous donations', function () {
    $contribution = Contribution::factory()->create([
        'user_id' => null,
        'type' => ContributionType::Donation,
        'monthly_key' => null,
    ]);

    expect($contribution->payer)->toBeNull()
        ->and($contribution->type)->toBe(ContributionType::Donation);
});

test('the monthly_key unique index blocks a duplicate monthly payment for the same member and month', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create();
    $registrar = User::factory()->create();
    $referenceMonth = now()->startOfMonth();

    Contribution::factory()->create([
        'community_id' => $community->id,
        'user_id' => $member->id,
        'registered_by' => $registrar->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'type' => ContributionType::Monthly,
    ]);

    expect(fn () => Contribution::factory()->create([
        'community_id' => $community->id,
        'user_id' => $member->id,
        'registered_by' => $registrar->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'type' => ContributionType::Monthly,
    ]))->toThrow(QueryException::class);
});

test('multiple donations with a null monthly_key for the same member and month do not collide', function () {
    $community = Community::factory()->create();
    $member = User::factory()->create();
    $registrar = User::factory()->create();

    Contribution::factory()->create([
        'community_id' => $community->id,
        'user_id' => $member->id,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Donation,
        'monthly_key' => null,
    ]);
    Contribution::factory()->create([
        'community_id' => $community->id,
        'user_id' => $member->id,
        'registered_by' => $registrar->id,
        'type' => ContributionType::Donation,
        'monthly_key' => null,
    ]);

    expect($community->contributions()->count())->toBe(2);
});

test('contribution report factory creates a valid row tied to a community', function () {
    $report = ContributionReport::factory()->create();

    expect($report->community)->toBeInstanceOf(Community::class)
        ->and($report->total_members)->not->toBeNull()
        ->and($report->generated_at)->not->toBeNull();
});

test('contribution setting currentFor treats a mid-month effective date as effective for that month', function () {
    $this->travelTo(now()->startOfMonth()->addDays(19));

    $community = Community::factory()->create();
    $referenceMonth = now()->startOfMonth();

    $setting = $community->contributionSettings()->create([
        'monthly_amount' => 50,
        'effective_from' => $referenceMonth->copy()->addDays(5),
    ]);

    $current = ContributionSetting::currentFor($community, $referenceMonth);

    expect($current?->id)->toBe($setting->id)
        ->and((float) $current->monthly_amount)->toBe(50.0);
});

test('contribution setting currentFor ignores a setting whose effective date is in the future', function () {
    $community = Community::factory()->create();

    $community->contributionSettings()->create([
        'monthly_amount' => 10,
        'effective_from' => now()->addDay(),
    ]);

    expect(ContributionSetting::currentFor($community, now()->startOfMonth()))->toBeNull();
});

test('contribution setting currentFor falls back to the latest setting already in effect', function () {
    $community = Community::factory()->create();
    $referenceMonth = now()->startOfMonth();

    $active = $community->contributionSettings()->create([
        'monthly_amount' => 5,
        'effective_from' => now()->subMonth(),
    ]);
    $community->contributionSettings()->create([
        'monthly_amount' => 10,
        'effective_from' => now()->addDay(),
    ]);

    $current = ContributionSetting::currentFor($community, $referenceMonth);

    expect($current?->id)->toBe($active->id)
        ->and((float) $current->monthly_amount)->toBe(5.0);
});
