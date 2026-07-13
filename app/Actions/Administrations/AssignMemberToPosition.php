<?php

namespace App\Actions\Administrations;

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\AdministrationMember;
use App\Models\Position;
use App\Models\User;

class AssignMemberToPosition
{
    /**
     * Assign a member to a position in an administration.
     */
    public function handle(Administration $administration, User $user, Position $position): AdministrationMember
    {
        $member = $administration->members()->updateOrCreate(
            ['user_id' => $user->id],
            ['position_id' => $position->id]
        );

        $this->syncCommunityRole($administration, $user, $position);

        return $member;
    }

    /**
     * Sync community_user.role when president position is assigned.
     */
    private function syncCommunityRole(Administration $administration, User $user, Position $position): void
    {
        $community = $administration->community;

        if ($community->current_administration_id !== $administration->id) {
            return;
        }

        if ($position->name === 'President') {
            $community->members()->updateExistingPivot($user->id, [
                'role' => CommunityRole::President->value,
            ]);
        }
    }
}
