<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    /**
     * Determine whether the user can view contributions overview/settings/reports.
     */
    public function viewAny(User $user, Community $community): bool
    {
        return $user->is_admin || $user->hasExecutivePositionIn($community);
    }

    /**
     * Determine whether the user can register payments/donations or change settings.
     */
    public function create(User $user, Community $community): bool
    {
        return $user->is_admin || $user->hasContributionOfficerPositionIn($community);
    }

    /**
     * Determine whether the user can reverse a registered payment.
     */
    public function revert(User $user, Contribution $contribution, Community $community): bool
    {
        return $contribution->community_id === $community->id
            && ($user->is_admin || $user->hasContributionOfficerPositionIn($community));
    }

    /**
     * Determine whether the user can view a member's own payment history.
     */
    public function viewOwnHistory(User $user, User $member, Community $community): bool
    {
        return $user->id === $member->id && $member->belongsToCommunity($community);
    }
}
