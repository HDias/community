<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCommunityContributionReport;
use App\Models\Community;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('contributions:generate-reports')]
#[Description('Dispatch defaulter report generation jobs for every community, for the month just ended.')]
class GenerateContributionReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $referenceMonth = Carbon::now()->subMonth()->startOfMonth();

        Community::query()->whereHas('members')->each(function (Community $community) use ($referenceMonth) {
            GenerateCommunityContributionReport::dispatch($community, $referenceMonth);
        });

        $this->info('Dispatched contribution report jobs for '.$referenceMonth->format('F Y').'.');
    }
}
