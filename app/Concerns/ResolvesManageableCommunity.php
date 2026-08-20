<?php

namespace App\Concerns;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
     * Resolve community from query param, failing validation when absent.
     *
     * @throws ValidationException
     */
    private function resolveRequiredCommunity(Request $request): Community
    {
        $community = $this->resolveCommunity($request);

        if (! $community) {
            throw ValidationException::withMessages([
                'community' => 'Community is required.',
            ]);
        }

        return $community;
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
