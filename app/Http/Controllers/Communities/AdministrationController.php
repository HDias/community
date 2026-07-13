<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Administrations\AssignMemberToPosition;
use App\Actions\Administrations\CreateAdministration;
use App\Http\Controllers\Controller;
use App\Models\Administration;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    /**
     * Display administration history.
     */
    public function index(Request $request): Response
    {
        $community = $this->resolveCommunity($request);

        if ($community) {
            Gate::authorize('manage', [Administration::class, $community]);
        }

        $administrations = $community
            ? $community->administrations()
                ->with(['members.user', 'members.position'])
                ->orderByDesc('started_at')
                ->get()
                ->map(fn (Administration $admin) => [
                    'id' => $admin->id,
                    'started_at' => $admin->started_at->toDateString(),
                    'ended_at' => $admin->ended_at?->toDateString(),
                    'is_current' => $community->current_administration_id === $admin->id,
                    'members' => $admin->members->map(fn ($m) => [
                        'id' => $m->id,
                        'user' => ['id' => $m->user->id, 'name' => $m->user->name],
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

        Gate::authorize('manage', [Administration::class, $community]);

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
                'members' => $administration->members->map(fn ($m) => [
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
    public function store(Request $request, CreateAdministration $action): RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        abort_unless($community, 400, 'Community is required.');

        Gate::authorize('manage', [Administration::class, $community]);

        $validated = $request->validate([
            'started_at' => ['required', 'date'],
        ]);

        $administration = $action->handle($community, $validated);

        return redirect()->route('administrations.show', $administration)
            ->with('success', 'Administration created.');
    }

    /**
     * Update administration dates.
     */
    public function update(Request $request, Administration $administration): RedirectResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community]);

        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        $administration->update($validated);

        if ($validated['ended_at'] && $community->current_administration_id === $administration->id) {
            $community->update(['current_administration_id' => null]);
        }

        if (! $validated['ended_at'] && ! $community->current_administration_id) {
            $community->update(['current_administration_id' => $administration->id]);
        }

        return redirect()->back()->with('success', 'Administration updated.');
    }

    /**
     * Delete an administration.
     */
    public function destroy(Administration $administration): RedirectResponse
    {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community]);

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
        Request $request,
        Administration $administration,
        AssignMemberToPosition $action
    ): RedirectResponse {
        $community = $administration->community;

        Gate::authorize('manage', [Administration::class, $community]);

        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('community_user', 'user_id')->where('community_id', $community->id)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('community_id', $community->id)],
        ]);

        $user = User::findOrFail($validated['user_id']);
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

        Gate::authorize('manage', [Administration::class, $community]);

        $administration->members()->where('user_id', $user->id)->delete();

        return redirect()->back()->with('success', 'Member removed.');
    }

    /**
     * Resolve community from query param.
     */
    private function resolveCommunity(Request $request): ?Community
    {
        $communityId = $request->query('community');

        if (! $communityId) {
            return null;
        }

        return Community::findOrFail($communityId);
    }

    /**
     * Get communities the user can manage.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function manageableCommunities(User $user): array
    {
        if ($user->is_admin) {
            return Community::orderBy('name')->get(['id', 'name'])->toArray();
        }

        return $user->communities()
            ->wherePivot('role', 'president')
            ->orderBy('name')
            ->get(['communities.id', 'communities.name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }
}
