<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class AdministrationPolicy
{
    /**
     * Determine whether the user can manage administrations.
     */
    public function manage(User $user, Community $community): bool
    {
        return $user->is_admin
            || $community->created_by === $user->id
            || $this->isCurrentPresident($user, $community);
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
