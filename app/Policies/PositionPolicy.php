<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    /**
     * Determine whether the user can manage positions.
     */
    public function manage(User $user, Community $community): bool
    {
        return $user->is_admin
            || $community->created_by === $user->id
            || $this->isCurrentPresident($user, $community);
    }

    /**
     * Determine whether the user can delete the position.
     */
    public function delete(User $user, Position $position): bool
    {
        $community = $position->community;

        if (! $this->manage($user, $community)) {
            return false;
        }

        if ($community->currentAdministration) {
            return ! $community->currentAdministration->members()
                ->where('position_id', $position->id)
                ->exists();
        }

        return true;
    }

    /**
     * Check if the user holds the President position in the current administration.
     */
    private function isCurrentPresident(User $user, Community $community): bool
    {
        $administration = $community->currentAdministration;

        if (! $administration) {
            return false;
        }

        return $administration->members()
            ->where('user_id', $user->id)
            ->whereHas('position', fn ($q) => $q->where('name', 'President'))
            ->exists();
    }
}
