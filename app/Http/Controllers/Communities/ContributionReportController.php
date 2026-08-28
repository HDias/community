<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\CalculateDefaultStreaks;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\ShowContributionReportRequest;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContributionReportController extends Controller
{
    use ResolvesManageableCommunity;

    public function index(ShowContributionReportRequest $request, CalculateDefaultStreaks $action): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('viewAny', [Contribution::class, $community]);

        $month = $request->validated('month');
        $referenceMonth = $month ? Carbon::parse($month) : Carbon::now();

        $streaks = $action->handle($community, $referenceMonth);
        $userNames = User::whereIn('id', $streaks->pluck('user_id'))->pluck('name', 'id');

        $defaulters = $streaks->map(fn ($row) => [
            ...$row,
            'name' => $userNames[$row['user_id']],
            'overdue_3plus' => $row['months_owed'] >= 3,
        ])->sortByDesc('months_owed')->values();

        $report = $community->contributionReports()
            ->where('reference_month', $referenceMonth->copy()->startOfMonth())
            ->first();

        return Inertia::render('contributions/report', [
            'community' => $community->only('id', 'name', 'slug'),
            'referenceMonth' => $referenceMonth->format('Y-m'),
            'report' => $report,
            'defaulters' => $defaulters,
        ]);
    }
}
