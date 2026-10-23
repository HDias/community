<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Carbon;

class RegisterDonation
{
    /**
     * Register a free-form donation, optionally anonymous.
     *
     * @param  array{amount: string, notes: string|null}  $data
     */
    public function handle(Community $community, User $registeredBy, ?User $member, array $data): Contribution
    {
        return $community->contributions()->create([
            'user_id' => $member?->id,
            'reference_month' => Carbon::now()->startOfMonth(),
            'monthly_key' => null,
            'amount' => $data['amount'],
            'registered_by' => $registeredBy->id,
            'type' => ContributionType::Donation,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
