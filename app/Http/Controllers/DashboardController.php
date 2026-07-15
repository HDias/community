<?php

namespace App\Http\Controllers;

use App\Enums\CommunityRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $community = $user->currentCommunity;

        $membership = $community
            ? $user->communities()->where('community_id', $community->id)->first()
            : null;

        $administration = $community?->currentAdministration
            ?->load('members.user', 'members.position');

        return Inertia::render('dashboard', [
            'community' => $community ? [
                'name' => $community->name,
                'slug' => $community->slug,
                'description' => $community->description,
            ] : null,
            'communities' => $user->communities()
                ->get(['communities.id', 'communities.name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                ->values(),
            'memberCount' => $community?->members()->count() ?? 0,
            'memberSince' => $membership?->pivot?->joined_at,
            'communityRole' => $membership?->pivot?->role,
            'administrationMembers' => $administration?->members->map(fn ($m) => [
                'name' => $m->user->name,
                'position' => $m->position->name,
            ])->values() ?? collect(),
            'canManage' => $user->is_admin
                || $membership?->pivot?->role === CommunityRole::President->value,
        ]);
    }
}
