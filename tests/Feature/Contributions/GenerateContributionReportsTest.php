<?php

use App\Actions\Contributions\GenerateContributionReports;
use App\Enums\CommunityRole;
use App\Enums\ContributionType;
use App\Jobs\GenerateCommunityContributionReport;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\ContributionReport;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

test('the console command dispatches one job per community that has members', function () {
    Bus::fake();

    $communityWithMembers = Community::factory()->create();
    $communityWithMembers->members()->attach(User::factory()->create()->id, [
        'role' => CommunityRole::Member->value,
        'joined_at' => now(),
    ]);

    Community::factory()->create();

    $this->artisan('contributions:generate-reports')->assertSuccessful();

    Bus::assertDispatchedTimes(GenerateCommunityContributionReport::class, 1);
    Bus::assertDispatched(fn (GenerateCommunityContributionReport $job): bool => $job->community->is($communityWithMembers));
});

test('the job creates a report with correct totals', function () {
    $community = Community::factory()->create();
    $registrar = User::factory()->create();

    $paidMember = User::factory()->create();
    $defaultingMember = User::factory()->create();

    foreach ([$paidMember, $defaultingMember] as $member) {
        $community->members()->attach($member->id, [
            'role' => CommunityRole::Member->value,
            'joined_at' => now()->subMonths(2),
        ]);
    }

    $referenceMonth = now()->startOfMonth();

    Contribution::factory()->create([
        'community_id' => $community->id,
        'user_id' => $paidMember->id,
        'registered_by' => $registrar->id,
        'reference_month' => $referenceMonth,
        'monthly_key' => $referenceMonth,
        'amount' => 50,
        'type' => ContributionType::Monthly,
    ]);

    (new GenerateCommunityContributionReport($community, $referenceMonth))->handle(app(GenerateContributionReports::class));

    $report = ContributionReport::where('community_id', $community->id)
        ->where('reference_month', $referenceMonth)
        ->first();

    expect($report)->not->toBeNull()
        ->and($report->total_members)->toBe(2)
        ->and($report->total_paid)->toBe(1)
        ->and($report->total_defaulting)->toBe(1)
        ->and((float) $report->total_collected)->toBe(50.0);
});

test('running the job twice for the same community and month updates the existing report', function () {
    $community = Community::factory()->create();
    $referenceMonth = now()->startOfMonth();

    $job = new GenerateCommunityContributionReport($community, $referenceMonth);
    $action = app(GenerateContributionReports::class);

    $job->handle($action);
    $job->handle($action);

    expect(ContributionReport::where('community_id', $community->id)->count())->toBe(1);
});
