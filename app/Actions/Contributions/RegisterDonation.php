<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Enums\DonorType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Carbon;

class RegisterDonation
{
    /**
     * Register a donation from a member, an individual or a legal entity.
     *
     * @param  array{donor_type: string, amount: string, notes: string|null, donor_name?: string|null, donor_document?: string|null}  $data
     */
    public function handle(Community $community, User $registeredBy, ?User $member, array $data): Contribution
    {
        $isMember = $data['donor_type'] === DonorType::Member->value;

        return $community->contributions()->create([
            'user_id' => $member?->id,
            'reference_month' => Carbon::now()->startOfMonth(),
            'monthly_key' => null,
            'amount' => $data['amount'],
            'registered_by' => $registeredBy->id,
            'type' => ContributionType::Donation,
            'donor_type' => $data['donor_type'],
            'donor_name' => $isMember ? null : ($data['donor_name'] ?? null),
            'donor_document' => $isMember ? null : preg_replace('/\D/', '', (string) ($data['donor_document'] ?? '')),
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
