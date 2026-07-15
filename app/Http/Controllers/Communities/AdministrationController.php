<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Administrations\AssignMemberToPosition;
use App\Actions\Administrations\CreateAdministration;
use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Communities\AssignMemberRequest;
use App\Http\Requests\Communities\StoreAdministrationRequest;
use App\Http\Requests\Communities\UpdateAdministrationRequest;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    use ResolvesManageableCommunity;

    /**
     * Display administration history.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            Gate::authorize('viewAny', Administration::class);

            $communities = $this->manageableCommunities($request->user());

            if (count($communities) === 1) {
                return redirect()->to('/administrations?community='.$communities[0]['id']);
            }
        }

        if ($community) {
            Gate::authorize('manage', [Administration::class, $community]);
        }

        $administrations = $community
            ? $community->administrations()
                ->with(['members.user', 'members.position'])
                ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$community->current_administration_id])
                ->orderByDesc('started_at')
                ->get()
                ->map(fn (Administration $admin) => [
                    'id' => $admin->id,
                    'started_at' => $admin->started_at->toDateString(),
                    'ended_at' => $admin->ended_at?->toDateString(),
                    'is_current' => $community->current_administration_id === $admin->id,
                    'members' => $admin->members->map(fn (AdministrationMember $m) => [
                        'id' => $m->id,
                        'user' => ['id' => $m->user->id, 'name' => $m->user->name, 'email' => $m->user->email],
                        'position' => ['id' => $m->position->id, 'name' => $m->position->name],
                    ]),
                ])
            : [];

        return Inertia::render('communities/administrations/index', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community?->only('id', 'name', 'slug'),
            'administrations' => $administrations,
        ]);
    }

    /**
     * Show a single administration.
     */
    public function show(Request $request, Administration $administration): Response
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        $administration->load(['members.user', 'members.position']);

        $availableMembers = $community->members()
            ->whereNotIn('users.id', $administration->members->pluck('user_id'))
            ->get(['users.id', 'users.name']);

        return Inertia::render('communities/administrations/show', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community->only('id', 'name', 'slug'),
            'administration' => [
                'id' => $administration->id,
                'started_at' => $administration->started_at->toDateString(),
                'ended_at' => $administration->ended_at?->toDateString(),
                'is_current' => $community->current_administration_id === $administration->id,
                'members' => $administration->members->map(fn (AdministrationMember $m) => [
                    'id' => $m->id,
                    'user' => ['id' => $m->user->id, 'name' => $m->user->name],
                    'position' => ['id' => $m->position->id, 'name' => $m->position->name],
                ]),
            ],
            'positions' => $community->positions->map(fn (Position $p) => ['id' => $p->id, 'name' => $p->name]),
            'availableMembers' => $availableMembers->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    /**
     * Store a new administration.
     */
    public function store(StoreAdministrationRequest $request, CreateAdministration $action): RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        abort_unless($community !== null, 400, 'Community is required.');

        Gate::authorize('manage', [Administration::class, $community]);

        $administration = $action->handle($community, $request->validated(), $request->user());

        return redirect()->route('administrations.show', $administration)
            ->with('success', 'Administration created.');
    }

    /**
     * Update administration dates.
     */
    public function update(UpdateAdministrationRequest $request, Administration $administration): RedirectResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        $validated = $request->validated();

        $administration->update($validated);

        if ($request->boolean('end_administration') && $community->current_administration_id === $administration->id) {
            $community->update(['current_administration_id' => null]);
        }

        return redirect()->back()->with('success', 'Administration updated.');
    }

    /**
     * Delete an administration.
     */
    public function destroy(Administration $administration): RedirectResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        if ($community->current_administration_id === $administration->id) {
            $community->update(['current_administration_id' => null]);
        }

        $administration->delete();

        return redirect()->route('administrations.index', ['community' => $community->id])
            ->with('success', 'Administration deleted.');
    }

    /**
     * Assign a member to a position.
     */
    public function assignMember(
        AssignMemberRequest $request,
        Administration $administration,
        AssignMemberToPosition $action
    ): RedirectResponse {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        $validated = $request->validated();

        /** @var User $user */
        $user = User::findOrFail($validated['user_id']);
        /** @var Position $position */
        $position = Position::findOrFail($validated['position_id']);

        $action->handle($administration, $user, $position);

        return redirect()->back()->with('success', 'Member assigned.');
    }

    /**
     * Remove a member from the administration.
     */
    public function removeMember(Administration $administration, User $user): RedirectResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        $administration->members()->where('user_id', $user->id)->delete();

        return redirect()->back()->with('success', 'Member removed.');
    }

    /**
     * Search available members for assignment.
     */
    public function searchMembers(Request $request, Administration $administration): JsonResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community, $administration]);

        $search = $request->query('search', '');

        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $assignedUserIds = $administration->members()->pluck('user_id');

        $members = $community->members()
            ->whereNotIn('users.id', $assignedUserIds)
            ->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['users.id', 'users.name', 'users.email']);

        return response()->json(
            $members->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
        );
    }
}
