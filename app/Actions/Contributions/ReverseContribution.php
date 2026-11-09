<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseContribution
{
    /**
     * Reverse a payment or donation, keeping the row for audit.
     *
     * Reversing a monthly payment frees the month for a fresh registration.
     */
    public function handle(Contribution $contribution, User $reversedBy, string $reason): Contribution
    {
        if ($contribution->reversed_at !== null) {
            throw ValidationException::withMessages([
                'reversal_reason' => __('This payment has already been reversed.'),
            ]);
        }

        return DB::transaction(function () use ($contribution, $reversedBy, $reason): Contribution {
            $attributes = [
                'reversed_at' => now(),
                'reversed_by' => $reversedBy->id,
                'reversal_reason' => $reason,
            ];

            if ($contribution->type === ContributionType::Monthly) {
                $attributes['monthly_key'] = null;
            }

            $contribution->forceFill($attributes)->save();

            return $contribution;
        });
    }
}
