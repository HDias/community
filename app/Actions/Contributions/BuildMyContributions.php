<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Carbon;

class BuildMyContributions
{
    /**
     * How many months ahead the "next due" lookup scans before assuming everything is paid.
     */
    private const NEXT_DUE_HORIZON_MONTHS = 12;

    /**
     * Build the contribution summary and history for a member in a community.
     *
     * @return array{
     *     totalPaidThisYear: string,
     *     currentMonthStatus: 'paid'|'pending',
     *     nextDueMonth: string|null,
     *     history: array<int, array<string, mixed>>
     * }
     */
    public function handle(User $user, Community $community): array
    {
        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();

        $activeMonthlyMonths = $community->contributions()
            ->where('user_id', $user->id)
            ->where('type', ContributionType::Monthly)
            ->whereNull('reversed_at')
            ->pluck('reference_month')
            ->map(fn (mixed $month): string => Carbon::parse($month)->format('Y-m'))
            ->flip();

        $nextDueMonth = null;

        foreach (range(0, self::NEXT_DUE_HORIZON_MONTHS - 1) as $offset) {
            $candidate = $currentMonth->copy()->addMonths($offset);

            if (! $activeMonthlyMonths->has($candidate->format('Y-m'))) {
                $nextDueMonth = $candidate->format('Y-m');

                break;
            }
        }

        $history = $community->contributions()
            ->where('user_id', $user->id)
            ->with(['registeredBy', 'reversedBy'])
            ->orderByDesc('reference_month')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Contribution $contribution): array => $this->historyRow($contribution))
            ->values()
            ->all();

        $totalPaidThisYear = $community->contributions()
            ->where('user_id', $user->id)
            ->whereNull('reversed_at')
            ->whereBetween('reference_month', [
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->endOfYear()->toDateString(),
            ])
            ->sum('amount');

        return [
            'totalPaidThisYear' => number_format((float) $totalPaidThisYear, 2, '.', ''),
            'currentMonthStatus' => $activeMonthlyMonths->has($currentMonth->format('Y-m')) ? 'paid' : 'pending',
            'nextDueMonth' => $nextDueMonth,
            'history' => $history,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function historyRow(Contribution $contribution): array
    {
        return [
            'id' => $contribution->id,
            'type' => $contribution->type === ContributionType::Donation ? 'donation' : 'monthly',
            'amount' => (string) $contribution->amount,
            'reference_month' => $contribution->reference_month->format('Y-m'),
            'status' => $contribution->reversed_at === null ? 'active' : 'reversed',
            'registered_at' => $contribution->created_at?->toIso8601String(),
            'registered_by_name' => $contribution->registeredBy?->name,
            'reversed_at' => $contribution->reversed_at?->toIso8601String(),
            'reversed_by_name' => $contribution->reversedBy?->name,
            'reversal_reason' => $contribution->reversal_reason,
        ];
    }
}
