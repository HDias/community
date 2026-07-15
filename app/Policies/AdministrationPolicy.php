<?php

namespace App\Policies;

use App\Models\Administration;
use App\Models\Community;
use App\Models\User;

class AdministrationPolicy
{
    /**
     * Determine whether the user can view the administrations list.
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
     * Determine whether the user can manage a specific administration.
     */
    public function manage(User $user, Community $community, ?Administration $administration = null): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($administration && $this->isOldAdministration($administration, $community)) {
            return false;
        }

        return $user->hasLeadershipPositionIn($community);
    }

    /**
     * Check if the administration is not the current one.
     */
    private function isOldAdministration(Administration $administration, Community $community): bool
    {
        return $community->current_administration_id !== $administration->id;
    }
}
