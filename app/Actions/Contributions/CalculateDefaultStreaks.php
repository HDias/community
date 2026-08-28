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

        return $joinDates->map(function ($joinedAt, $userId) use ($referenceMonth, $paidMonths) {
            $joined = Carbon::parse($joinedAt)->startOfMonth();
            $expectedMonths = collect();

            for ($month = $joined->copy(); $month->lte($referenceMonth); $month->addMonth()) {
                $expectedMonths->push($month->copy());
            }

            $paid = $paidMonths->get($userId, collect());
            $owedMonths = $expectedMonths->reject(fn ($month) => $paid->contains(fn ($p) => $p->isSameMonth($month)));

            return [
                'user_id' => $userId,
                'months_owed' => $owedMonths->count(),
                'last_payment' => $paid->last(),
            ];
        })->filter(fn ($row) => $row['months_owed'] > 0)->values();
    }
}
