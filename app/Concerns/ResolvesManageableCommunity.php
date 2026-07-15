<?php

namespace App\Concerns;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesManageableCommunity
{
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
            ->orderBy('name')
            ->get(['communities.id', 'communities.name', 'communities.current_administration_id'])
            ->filter(fn (Community $c) => $user->hasExecutivePositionIn($c))
            ->map(fn (Community $c) => ['id' => $c->id, 'name' => $c->name])
            ->values()
            ->toArray();
    }
}
