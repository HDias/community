<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\ContributionSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class RegisterContribution
{
    /**
     * Register a monthly payment for a member, blocking duplicates for the same reference month.
     */
    public function handle(Community $community, User $registeredBy, User $member, CarbonInterface $referenceMonth): Contribution
    {
        $referenceMonth = $referenceMonth->copy()->startOfMonth();

        $exists = $community->contributions()
            ->where('user_id', $member->id)
            ->where('reference_month', $referenceMonth)
            ->where('type', ContributionType::Monthly)
            ->whereNull('reversed_at')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reference_month' => __('This member already has a payment registered for :month.', [
                    'month' => $referenceMonth->format('F Y'),
                ]),
            ]);
        }

        $setting = ContributionSetting::currentFor($community, $referenceMonth);

        if (! $setting) {
            throw ValidationException::withMessages([
                'amount' => __('The monthly contribution amount is not configured for :month.', [
                    'month' => $referenceMonth->format('F Y'),
                ]),
            ]);
        }

        return $community->contributions()->create([
            'user_id' => $member->id,
            'reference_month' => $referenceMonth,
            'monthly_key' => $referenceMonth,
            'amount' => $setting->monthly_amount,
            'registered_by' => $registeredBy->id,
            'type' => ContributionType::Monthly,
        ]);
    }
}
