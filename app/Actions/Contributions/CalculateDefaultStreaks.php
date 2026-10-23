<?php

namespace App\Actions\Contributions;

use App\Enums\ContributionType;
use App\Models\Community;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalculateDefaultStreaks
{
    /**
     * @return Collection<int, array{user_id: int, months_owed: int, last_payment: Carbon|null}>
     */
    public function handle(Community $community, CarbonInterface $referenceMonth): Collection
    {
        $referenceMonth = $referenceMonth->copy()->startOfMonth();
        $joinDates = $community->members()->pluck('community_user.joined_at', 'users.id');

        $paidMonths = $community->contributions()
            ->where('type', ContributionType::Monthly)
            ->where('reference_month', '<=', $referenceMonth)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('reference_month')->sort());

        $rows = [];

        foreach ($joinDates as $userId => $joinedAt) {
            $userId = (int) $userId;
            $joined = Carbon::parse($joinedAt)->startOfMonth();
            $expectedMonths = collect();

            for ($month = $joined->copy(); $month->lte($referenceMonth); $month->addMonth()) {
                $expectedMonths->push($month->copy());
            }

            $paid = $paidMonths->get($userId, collect());
            $owedMonths = $expectedMonths->reject(fn ($month) => $paid->contains(fn ($p) => $p->isSameMonth($month)));
            $monthsOwed = $owedMonths->count();

            if ($monthsOwed === 0) {
                continue;
            }

            $rows[] = [
                'user_id' => $userId,
                'months_owed' => $monthsOwed,
                'last_payment' => $paid->last(),
            ];
        }

        // PHPStan/Larastan false positive: Collection<int, array{...}>'s invariant
        // TValue check fails whenever the array shape has a nullable member (e.g.
        // `last_payment: Carbon|null`), even when the declared and inferred types
        // are textually identical. Confirmed via minimal repro outside this class —
        // a non-nullable union in the same position passes. See
        // https://phpstan.org/blog/whats-up-with-template-covariant.
        // @phpstan-ignore return.type
        return new Collection($rows);
    }
}
