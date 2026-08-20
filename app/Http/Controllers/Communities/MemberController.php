<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Members\RegisterMember;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\AdministrationMember;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    use ResolvesManageableCommunity;

    /**
     * Display community members.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            Gate::authorize('viewAny', User::class);

            $communities = $this->manageableCommunities($request->user());

            if (count($communities) === 1) {
                return redirect()->to('/members?community='.$communities[0]['id']);
            }

            return Inertia::render('members/index', [
                'communities' => $communities,
                'community' => null,
                'members' => [],
            ]);
        }

        Gate::authorize('viewAny', [User::class, $community]);

        $members = $community->members()
            ->with('profile')
            ->orderBy('name')
            ->paginate(15);

        $positions = $this->positionsByUser($community, $members->pluck('id')->all());

        $members->through(function (User $member) use ($positions): User {
            return $member->setAttribute('position', $positions[$member->id] ?? null);
        });

        return Inertia::render('members/index', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community->only('id', 'name', 'slug'),
            'members' => $members,
        ]);
    }

    /**
     * Map user ids to the position name they hold in the community's current administration.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, string>
     */
    private function positionsByUser(Community $community, array $userIds): array
    {
        if ($community->current_administration_id === null || $userIds === []) {
            return [];
        }

        return AdministrationMember::query()
            ->join('positions', 'positions.id', '=', 'administration_members.position_id')
            ->where('administration_members.administration_id', $community->current_administration_id)
            ->whereIn('administration_members.user_id', $userIds)
            ->pluck('positions.name', 'administration_members.user_id')
            ->all();
    }

    /**
     * Show the member creation form.
     */
    public function create(Request $request): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [User::class, $community]);

        return Inertia::render('members/create', [
            'community' => $community->only('id', 'name', 'slug'),
        ]);
    }

    /**
     * Store a new member.
     */
    public function store(StoreMemberRequest $request, RegisterMember $action): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('create', [User::class, $community]);

        $action->handle($community, $request->validated());

        return redirect()->route('members.index', ['community' => $community->id])
            ->with('success', __('Member registered successfully.'));
    }

    /**
     * Show the member edit form.
     */
    public function edit(Request $request, User $member): Response
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('update', [User::class, $member, $community]);

        $member->load('profile');

        return Inertia::render('members/edit', [
            'member' => $member,
            'community' => $community->only('id', 'name', 'slug'),
        ]);
    }

    /**
     * Update a member.
     */
    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $community = $this->resolveRequiredCommunity($request);

        Gate::authorize('update', [User::class, $member, $community]);

        $member->update($request->safe()->only(['name', 'email']));

        $profileData = $request->safe()->except(['name', 'email']);
        if ($member->profile) {
            $member->profile->update($profileData);
        } else {
            $member->profile()->create($profileData);
        }

        return redirect()->route('members.index', ['community' => $community->id])
            ->with('success', __('Member updated successfully.'));
    }
}
