<?php

namespace App\Http\Controllers\Communities;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    /**
     * Display community positions.
     */
    public function index(Request $request): Response
    {
        $community = $this->resolveCommunity($request);

        if ($community) {
            Gate::authorize('manage', [Position::class, $community]);
        }

        return Inertia::render('communities/positions/index', [
            'communities' => $this->manageableCommunities($request->user()),
            'community' => $community?->only('id', 'name', 'slug'),
            'positions' => $community ? $community->positions->map(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
                'is_default' => $position->is_default,
                'in_use' => $community->currentAdministration
                    ? $community->currentAdministration->members()->where('position_id', $position->id)->exists()
                    : false,
            ]) : [],
        ]);
    }

    /**
     * Store a new position.
     */
    public function store(Request $request): RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        abort_unless($community !== null, 400, 'Community is required.');

        Gate::authorize('manage', [Position::class, $community]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $community->positions()->create($validated);

        return redirect()->back()->with('success', 'Position created.');
    }

    /**
     * Update a position.
     */
    public function update(Request $request, Position $position): RedirectResponse
    {
        $community = $position->community;

        Gate::authorize('manage', [Position::class, $community]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $position->update($validated);

        return redirect()->back()->with('success', 'Position updated.');
    }

    /**
     * Delete a position.
     */
    public function destroy(Position $position): RedirectResponse
    {
        Gate::authorize('delete', $position);

        $position->delete();

        return redirect()->back()->with('success', 'Position deleted.');
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

        return Community::findOrFail((int) $communityId);
    }

    /**
     * Get communities the user can manage.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function manageableCommunities(User $user): array
    {
        if ($user->is_admin) {
            return Community::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Community $c) => ['id' => $c->id, 'name' => $c->name])
                ->toArray();
        }

        return $user->communities()
            ->wherePivot('role', 'president')
            ->orderBy('name')
            ->get(['communities.id', 'communities.name'])
            ->map(fn (Community $c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }
}
