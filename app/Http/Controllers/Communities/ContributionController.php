<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\RegisterContribution;
use App\Actions\Contributions\RegisterDonation;
use App\Concerns\ResolvesManageableCommunity;
use App\Enums\ContributionType;
use App\Enums\DonorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\ShowContributionIndexRequest;
use App\Http\Requests\Contributions\StoreContributionRequest;
use App\Http\Requests\Contributions\StoreDonationRequest;
use App\Models\Contribution;
use App\Models\ContributionSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    use ResolvesManageableCommunity;

    /**
     * Display the contributions overview and payment registration form.
     */
    public function index(ShowContributionIndexRequest $request): Response|RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            Gate::authorize('viewAny', User::class);

            $communities = $this->manageableCommunities($request->user());

            if (count($communities) === 1) {
                return redirect()->to('/contributions?community='.$communities[0]['id']);
            }

            return Inertia::render('contributions/index', [
                'communities' => $communities,
                'community' => null,
                'members' => [],
                'monthlyAmount' => null,
                'referenceMonth' => Carbon::now()->format('Y-m'),
                'search' => null,
            ]);
        }

        Gate::authorize('viewAny', [Contribution::class, $community]);

        $month = $request->validated('month');
        $referenceMonth = $month ? Carbon::parse($month) : Carbon::now();
        $referenceMonth = $referenceMonth->copy()->startOfMonth();
        $search = $request->validated('search');

        $paidUserIds = $community->contributions()
            ->where('type', ContributionType::Monthly)
            ->where('reference_month', $referenceMonth)
            ->pluck('user_id')
            ->all();

        $members = $community->members()
            ->select(['users.id', 'users.name'])
            ->with('profile')
            ->when($search, function ($query, string $search): void {
                $digits = preg_replace('/\D/', '', $search);

                $query->where(function ($query) use ($search, $digits): void {
                    $query->where('users.name', 'like', "%{$search}%");

                    if ($digits !== '') {
                        $query->orWhereHas('profile', fn ($query) => $query->where('cpf', 'like', "%{$digits}%"));
                    }
                });
            })
            ->orderBy('users.name')
            ->paginate((int) config('pagination.per_page'))
            ->withQueryString()
            ->through(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'cpf' => $member->profile?->cpf,
                'paid' => in_array($member->id, $paidUserIds, true),
            ]);

        $setting = ContributionSetting::currentFor($community, $referenceMonth);

        return Inertia::render('contributions/index', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community->only('id', 'name', 'slug'),
            'members' => $members,
            'monthlyAmount' => $setting?->monthly_amount,
            'referenceMonth' => $referenceMonth->format('Y-m'),
            'search' => $search,
        ]);
    }

    /**
     * Show the donation registration form.
     */
    public function createDonation(Request $request): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        $members = $community->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
            ]);

        return Inertia::render('contributions/donations/create', [
            'community' => $community->only('id', 'name', 'slug'),
            'members' => $members,
        ]);
    }

    public function store(StoreContributionRequest $request, RegisterContribution $action): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        $member = User::findOrFail((int) $request->validated('user_id'));

        $action->handle($community, $request->user(), $member, Carbon::parse($request->validated('reference_month')));

        return redirect()->route('contributions.index', ['community' => $community->id])
            ->with('success', __('Payment registered successfully.'));
    }

    public function storeDonation(StoreDonationRequest $request, RegisterDonation $action): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [Contribution::class, $community]);

        $donorType = DonorType::from($request->validated('donor_type'));

        $member = $donorType === DonorType::Member
            ? User::findOrFail((int) $request->validated('user_id'))
            : null;

        $action->handle($community, $request->user(), $member, [
            'donor_type' => $donorType->value,
            'donor_name' => $request->validated('donor_name'),
            'donor_document' => $request->validated('donor_document'),
            'amount' => $request->validated('amount'),
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('contributions.index', ['community' => $community->id])
            ->with('success', __('Donation registered successfully.'));
    }
}
