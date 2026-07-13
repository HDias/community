<?php

namespace App\Actions\Administrations;

use App\Models\Administration;
use App\Models\Community;
use Illuminate\Support\Facades\DB;

class CreateAdministration
{
    /**
     * Create a new administration, ending the previous one.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Community $community, array $data): Administration
    {
        return DB::transaction(function () use ($community, $data) {
            if ($community->currentAdministration) {
                $community->currentAdministration->update([
                    'ended_at' => $data['started_at'],
                ]);
            }

            /** @var Administration $administration */
            $administration = $community->administrations()->create([
                'started_at' => $data['started_at'],
            ]);

            $community->update(['current_administration_id' => $administration->id]);

            return $administration;
        });
    }
}
