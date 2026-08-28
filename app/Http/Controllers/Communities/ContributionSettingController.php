<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\UpdateContributionSetting;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\UpdateContributionSettingRequest;
use App\Models\Contribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContributionSettingController extends Controller
{
    use ResolvesManageableCommunity;

    public function index(Request $request): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('viewAny', [Contribution::class, $community]);

        return Inertia::render('contributions/settings', [
            'community' => $community->only('id', 'name', 'slug'),
            'settings' => $community->contributionSettings()->orderByDesc('effective_from')->get(),
        ]);
    }

    public function store(UpdateContributionSettingRequest $request, UpdateContributionSetting $action): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        $action->handle($community, [
            'monthly_amount' => $request->validated('monthly_amount'),
            'effective_from' => $request->validated('effective_from'),
        ]);

        return redirect()->route('contributions.settings.index', ['community' => $community->id])
            ->with('success', __('Contribution amount updated.'));
    }
}
