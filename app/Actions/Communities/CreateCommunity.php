<?php

namespace App\Actions\Communities;

use App\Enums\CommunityRole;
use App\Models\Administration;
use App\Models\Community;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;

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

        $this->seedDefaultPositionsAndAdministration($community, $user);

        $user->switchCommunity($community);

        return $community;
    }

    /**
     * Seed default positions and create the first administration.
     */
    private function seedDefaultPositionsAndAdministration(Community $community, User $user): void
    {
        $defaultPositions = ['President', 'Vice-President', 'Secretary', 'Treasurer'];

        /** @var Collection<int, Position> $positions */
        $positions = collect($defaultPositions)->map(
            fn (string $name) => $community->positions()->create([
                'name' => $name,
                'is_default' => true,
            ])
        );

        /** @var Administration $administration */
        $administration = $community->administrations()->create([
            'started_at' => now(),
        ]);

        $administration->members()->create([
            'user_id' => $user->id,
            'position_id' => $positions->first()->id,
        ]);

        $community->update(['current_administration_id' => $administration->id]);
    }
}
