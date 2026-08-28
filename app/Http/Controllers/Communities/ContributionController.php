<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\RegisterContribution;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\StoreContributionRequest;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ContributionController extends Controller
{
    use ResolvesManageableCommunity;

    public function store(StoreContributionRequest $request, RegisterContribution $action): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        $member = User::findOrFail($request->validated('user_id'));

        $action->handle($community, $request->user(), $member, Carbon::parse($request->validated('reference_month')));

        return redirect()->to('/contributions?community='.$community->id)
            ->with('success', __('Payment registered successfully.'));
    }
}
