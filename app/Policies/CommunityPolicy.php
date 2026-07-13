<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    /**
     * Determine whether the user can create communities.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can view the community.
     */
    public function view(User $user, Community $community): bool
    {
        return $user->belongsToCommunity($community);
    }

    /**
     * Determine whether the user can update the community.
     */
    public function update(User $user, Community $community): bool
    {
        return $user->is_admin || $community->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the community.
     */
    public function delete(User $user, Community $community): bool
    {
        return $user->is_admin || $community->created_by === $user->id;
    }
}
