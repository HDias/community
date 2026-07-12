<?php

namespace App\Actions\Communities;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;

class CreateCommunity
{
    /**
     * Create a new community and attach the creator as president.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Community
    {
        $community = Community::create([
            ...$data,
            'created_by' => $user->id,
        ]);

        $community->members()->attach($user->id, [
            'role' => CommunityRole::President->value,
            'joined_at' => now(),
        ]);

        $user->switchCommunity($community);

        return $community;
    }
}
