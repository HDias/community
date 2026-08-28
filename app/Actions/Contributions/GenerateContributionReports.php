<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\ContributionReport;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class GenerateContributionReports
{
    /**
     * Generate (or replace) the defaulter report for a community and reference month.
     */
    public function handle(Community $community, CarbonInterface $referenceMonth): ContributionReport
    {
        $referenceMonth = $referenceMonth->copy()->startOfMonth();

        $totalMembers = $community->members()->count();

        $paidContributions = $community->contributions()
            ->where('reference_month', $referenceMonth)
            ->where('type', ContributionType::Monthly)
            ->get();

        $totalPaid = $paidContributions->count();
        $totalCollected = $paidContributions->sum('amount');
        $totalDefaulting = max($totalMembers - $totalPaid, 0);

        return ContributionReport::updateOrCreate(
            ['community_id' => $community->id, 'reference_month' => $referenceMonth],
            [
                'total_members' => $totalMembers,
                'total_paid' => $totalPaid,
                'total_defaulting' => $totalDefaulting,
                'total_collected' => $totalCollected,
                'generated_at' => Carbon::now(),
            ],
        );
    }
}
