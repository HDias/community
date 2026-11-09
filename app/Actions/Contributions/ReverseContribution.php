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
     * Reverse a monthly payment, keeping the row for audit and freeing the month.
     */
    public function handle(Contribution $contribution, User $reversedBy, string $reason): Contribution
    {
        if ($contribution->reversed_at !== null) {
            throw ValidationException::withMessages([
                'reversal_reason' => __('This payment has already been reversed.'),
            ]);
        }

        if ($contribution->type !== ContributionType::Monthly) {
            throw ValidationException::withMessages([
                'reversal_reason' => __('Only monthly payments can be reversed.'),
            ]);
        }

        return DB::transaction(function () use ($contribution, $reversedBy, $reason): Contribution {
            $contribution->forceFill([
                'reversed_at' => now(),
                'reversed_by' => $reversedBy->id,
                'reversal_reason' => $reason,
                'monthly_key' => null,
            ])->save();

            return $contribution;
        });
    }
}
