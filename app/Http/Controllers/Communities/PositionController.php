<?php

namespace App\Http\Controllers\Communities;

use App\Concerns\ResolvesManageableCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Communities\SavePositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    use ResolvesManageableCommunity;

    /**
     * Display community positions.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            Gate::authorize('viewAny', Position::class);

            $communities = $this->manageableCommunities($request->user());

            if (count($communities) === 1) {
                return redirect()->to('/positions?community='.$communities[0]['id']);
            }
        }

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
                'has_admin_access' => $position->has_admin_access,
                'in_use' => $community->currentAdministration
                    ? $community->currentAdministration->members()->where('position_id', $position->id)->exists()
                    : false,
            ]) : [],
        ]);
    }

    /**
     * Store a new position.
     */
    public function store(SavePositionRequest $request): RedirectResponse
    {
        $community = $this->resolveCommunity($request);

        abort_unless($community !== null, 400, 'Community is required.');

        Gate::authorize('manage', [Position::class, $community]);

        $community->positions()->create($request->validated());

        return redirect()->back()->with('success', 'Position created.');
    }

    /**
     * Update a position.
     */
    public function update(SavePositionRequest $request, Position $position): RedirectResponse
    {
        $community = $position->community;

        Gate::authorize('manage', [Position::class, $community]);

        $position->update($request->validated());

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
}
