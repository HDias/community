<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\UpdateContributionSetting;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\UpdateContributionSettingRequest;
use App\Models\Contribution;
use App\Models\ContributionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContributionSettingController extends Controller
{
    use ResolvesManageableCommunity;

    public function index(Request $request): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('viewAny', [Contribution::class, $community]);

        $settings = $community->contributionSettings()
            ->orderByDesc('effective_from')
            ->get()
            ->map(fn (ContributionSetting $setting): array => [
                'id' => $setting->id,
                'monthly_amount' => $setting->monthly_amount,
                'effective_from' => $setting->effective_from->format('Y-m-d'),
                'editable' => $setting->effective_from->isAfter(today()),
            ]);

        return Inertia::render('contributions/settings', [
            'community' => $community->only('id', 'name', 'slug'),
            'settings' => $settings,
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

    public function update(
        UpdateContributionSettingRequest $request,
        ContributionSetting $contributionSetting,
        UpdateContributionSetting $action,
    ): RedirectResponse {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        abort_unless($contributionSetting->community_id === $community->id, 403);

        if (! $contributionSetting->effective_from->isAfter(today())) {
            throw ValidationException::withMessages([
                'effective_from' => __('Only settings with a future effective date can be edited.'),
            ]);
        }

        $duplicateDate = $community->contributionSettings()
            ->where('id', '!=', $contributionSetting->id)
            ->whereDate('effective_from', $request->validated('effective_from'))
            ->exists();

        if ($duplicateDate) {
            throw ValidationException::withMessages([
                'effective_from' => __('A setting already exists for this effective date.'),
            ]);
        }

        $action->update($contributionSetting, [
            'monthly_amount' => $request->validated('monthly_amount'),
            'effective_from' => $request->validated('effective_from'),
        ]);

        return redirect()->route('contributions.settings.index', ['community' => $community->id])
            ->with('success', __('Contribution amount updated.'));
    }
}
