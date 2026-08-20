<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can view the members list.
     */
    public function viewAny(User $user, ?Community $community = null): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($community) {
            return $user->hasExecutivePositionIn($community);
        }

        return $user->communities()
            ->get(['communities.id', 'communities.current_administration_id'])
            ->contains(fn (Community $c): bool => $user->hasExecutivePositionIn($c));
    }

    /**
     * Determine whether the user can register new members.
     */
    public function create(User $user, Community $community): bool
    {
        return $user->is_admin || $user->hasExecutivePositionIn($community);
    }

    /**
     * Determine whether the user can update a member.
     */
    public function update(User $user, User $member, Community $community): bool
    {
        return ($user->is_admin || $user->hasExecutivePositionIn($community))
            && $member->belongsToCommunity($community);
    }
}
