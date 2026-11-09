<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Contributions\RegisterContribution;
use App\Actions\Contributions\RegisterDonation;
use App\Actions\Contributions\ReverseContribution;
use App\Concerns\ResolvesManageableCommunity;
use App\Enums\ContributionType;
use App\Enums\DonorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\ShowContributionIndexRequest;
use App\Http\Requests\Contributions\ShowDonationIndexRequest;
use App\Http\Requests\Contributions\StoreContributionRequest;
use App\Http\Requests\Contributions\StoreContributionReversalRequest;
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

        $paidContributions = $community->contributions()
            ->where('type', ContributionType::Monthly)
            ->where('reference_month', $referenceMonth)
            ->whereNull('reversed_at')
            ->pluck('id', 'user_id');

        $history = $community->contributions()
            ->where('type', ContributionType::Monthly)
            ->where('reference_month', $referenceMonth)
            ->with(['registeredBy', 'reversedBy'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

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
            ->through(function (User $member) use ($paidContributions, $history): array {
                $contributionId = $paidContributions[$member->id] ?? null;

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'cpf' => $member->profile?->cpf,
                    'contribution_id' => $contributionId,
                    'paid' => $contributionId !== null,
                    'history' => $history->get($member->id, collect())
                        ->map(fn (Contribution $contribution): array => $this->historyRow($contribution))
                        ->values()
                        ->all(),
                ];
            });

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
     * Display the donations ledger for a community.
     */
    public function indexDonations(ShowDonationIndexRequest $request): Response|RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            Gate::authorize('viewAny', User::class);

            $communities = $this->manageableCommunities($request->user());

            if (count($communities) === 1) {
                return redirect()->to('/contributions/donations?community='.$communities[0]['id']);
            }

            return Inertia::render('contributions/donations/index', [
                'communities' => $communities,
                'community' => null,
                'donations' => [],
                'referenceMonth' => null,
                'search' => null,
            ]);
        }

        Gate::authorize('viewAny', [Contribution::class, $community]);

        $month = $request->validated('month');
        $search = $request->validated('search');

        $donations = $community->contributions()
            ->where('type', ContributionType::Donation)
            ->with(['payer', 'registeredBy', 'reversedBy'])
            ->when($month, fn ($query) => $query->where(
                'reference_month',
                Carbon::parse($month)->startOfMonth(),
            ))
            ->when($search, function ($query, string $search): void {
                $digits = preg_replace('/\D/', '', $search);

                $query->where(function ($query) use ($search, $digits): void {
                    $query->where('donor_name', 'like', "%{$search}%")
                        ->orWhereHas('payer', fn ($query) => $query->where('name', 'like', "%{$search}%"));

                    if ($digits !== '') {
                        $query->orWhere('donor_document', 'like', "%{$digits}%");
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) config('pagination.per_page'))
            ->withQueryString()
            ->through(fn (Contribution $donation): array => $this->donationRow($donation));

        return Inertia::render('contributions/donations/index', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community->only('id', 'name', 'slug'),
            'donations' => $donations,
            'referenceMonth' => $month,
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
            'referenceMonth' => Carbon::now()->format('Y-m'),
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

        return redirect()->route('contributions.donations.index', [
            'community' => $community->id,
            'month' => Carbon::now()->format('Y-m'),
        ])->with('success', __('Donation registered successfully.'));
    }

    public function revert(
        StoreContributionReversalRequest $request,
        Contribution $contribution,
        ReverseContribution $action,
    ): RedirectResponse {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('revert', [$contribution, $community]);

        $action->handle(
            $contribution,
            $request->user(),
            $request->validated('reversal_reason'),
        );

        if ($contribution->type === ContributionType::Donation) {
            return redirect()->route('contributions.donations.index', [
                'community' => $community->id,
                'month' => $contribution->reference_month->format('Y-m'),
            ])->with('success', __('Payment reversed successfully.'));
        }

        return redirect()->route('contributions.index', [
            'community' => $community->id,
            'month' => $contribution->reference_month->format('Y-m'),
        ])->with('success', __('Payment reversed successfully.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function historyRow(Contribution $contribution): array
    {
        return [
            'id' => $contribution->id,
            'amount' => (string) $contribution->amount,
            'status' => $contribution->reversed_at === null ? 'active' : 'reversed',
            'registered_at' => $contribution->created_at?->toIso8601String(),
            'registered_by_name' => $contribution->registeredBy?->name,
            'reversed_at' => $contribution->reversed_at?->toIso8601String(),
            'reversed_by_name' => $contribution->reversedBy?->name,
            'reversal_reason' => $contribution->reversal_reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function donationRow(Contribution $donation): array
    {
        return [
            'id' => $donation->id,
            'donor_type' => $donation->donor_type?->value,
            'donor_name' => $donation->donor_name,
            'donor_document' => $donation->donor_document,
            'member_name' => $donation->payer?->name,
            'amount' => (string) $donation->amount,
            'reference_month' => $donation->reference_month->format('Y-m'),
            'notes' => $donation->notes,
            'status' => $donation->reversed_at === null ? 'active' : 'reversed',
            'registered_at' => $donation->created_at?->toIso8601String(),
            'registered_by_name' => $donation->registeredBy?->name,
            'reversed_at' => $donation->reversed_at?->toIso8601String(),
            'reversed_by_name' => $donation->reversedBy?->name,
            'reversal_reason' => $donation->reversal_reason,
        ];
    }
}
