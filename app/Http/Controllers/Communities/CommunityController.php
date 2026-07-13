<?php

namespace App\Http\Controllers\Communities;

use App\Actions\Communities\CreateCommunity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Communities\SaveCommunityRequest;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    /**
     * Display the communities list (Card Grid or Onboarding Hub).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $user->is_admin
            ? Community::query()
            : $user->communities();

        $communities = $query
            ->withCount('members')
            ->get()
            ->map(fn (Community $community) => [
                'id' => $community->id,
                'name' => $community->name,
                'slug' => $community->slug,
                'description' => $community->description,
                'address' => $community->address,
                'city' => $community->city,
                'state' => $community->state,
                'members_count' => $community->members_count,
                'role' => $community->pivot?->role ?? 'admin',
                'is_current' => $user->current_community_id === $community->id,
                'is_owner' => $community->created_by === $user->id || $user->is_admin,
            ]);

        if ($communities->isEmpty()) {
            return Inertia::render('communities/onboarding', [
                'canCreate' => $user->is_admin,
            ]);
        }

        return Inertia::render('communities/index', [
            'communities' => $communities,
            'canCreate' => $user->is_admin,
        ]);
    }

    /**
     * Store a newly created community.
     */
    public function store(SaveCommunityRequest $request, CreateCommunity $action): RedirectResponse
    {
        Gate::authorize('create', Community::class);

        $community = $action->handle($request->user(), $request->validated());

        return redirect()->route('communities.index')
            ->with('success', "Community \"{$community->name}\" created.");
    }

    /**
     * Show the community edit form.
     */
    public function edit(Community $community): Response
    {
        Gate::authorize('update', $community);

        return Inertia::render('communities/edit', [
            'community' => $community->only('id', 'name', 'slug', 'description', 'address', 'city', 'state'),
        ]);
    }

    /**
     * Update the community.
     */
    public function update(SaveCommunityRequest $request, Community $community): RedirectResponse
    {
        Gate::authorize('update', $community);

        $community->update($request->validated());

        return redirect()->route('communities.index')
            ->with('success', 'Community updated.');
    }

    /**
     * Soft delete the community.
     */
    public function destroy(Request $request, Community $community): RedirectResponse
    {
        Gate::authorize('delete', $community);

        User::where('current_community_id', $community->id)
            ->update(['current_community_id' => null]);

        $community->delete();

        return redirect()->route('communities.index')
            ->with('success', 'Community deleted.');
    }

    /**
     * Switch the user's current community.
     */
    public function switchCommunity(Request $request, Community $community): RedirectResponse
    {
        $user = $request->user();

        if (! $user->belongsToCommunity($community)) {
            abort(403);
        }

        $user->switchCommunity($community);

        return redirect()->back();
    }
}
