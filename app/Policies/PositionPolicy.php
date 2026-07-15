<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    /**
     * Determine whether the user can view the positions list.
     */
    public function viewAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $community = $user->currentCommunity;

        return $community && $user->hasExecutivePositionIn($community);
    }

    /**
     * Determine whether the user can manage (create/edit) positions.
     */
    public function manage(User $user, Community $community): bool
    {
        return $user->is_admin || $user->hasExecutivePositionIn($community);
    }

    /**
     * Determine whether the user can delete the position.
     */
    public function delete(User $user, Position $position): bool
    {
        if (! $user->is_admin) {
            return false;
        }

        $community = $position->community;

        if ($community->currentAdministration) {
            return ! $community->currentAdministration->members()
                ->where('position_id', $position->id)
                ->exists();
        }

        return true;
    }
}
