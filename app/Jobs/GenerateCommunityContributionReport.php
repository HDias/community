<?php

namespace App\Jobs;

use App\Actions\Contributions\GenerateContributionReports;
use App\Models\Community;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateCommunityContributionReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Community $community,
        public CarbonInterface $referenceMonth,
    ) {}

    public function handle(GenerateContributionReports $action): void
    {
        $action->handle($this->community, $this->referenceMonth);
    }
}
