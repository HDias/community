<?php

namespace App\Actions\Contributions;

use App\Models\Community;
use App\Models\ContributionSetting;

class UpdateContributionSetting
{
    /**
     * Insert a new contribution setting, preserving prior history.
     *
     * @param  array{monthly_amount: string, effective_from: string}  $data
     */
    public function handle(Community $community, array $data): ContributionSetting
    {
        return $community->contributionSettings()->create($data);
    }
}
