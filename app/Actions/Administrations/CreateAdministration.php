<?php

namespace App\Actions\Administrations;

use App\Models\Administration;
use App\Models\Community;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAdministration
{
    /**
     * Create a new administration, ending the previous one.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Community $community, array $data, ?User $creator = null): Administration
    {
        return DB::transaction(function () use ($community, $data, $creator) {
            $previousAdmin = $community->currentAdministration;

            if ($previousAdmin) {
                $previousAdmin->update([
                    'ended_at' => $data['started_at'],
                ]);
            }

            /** @var Administration $administration */
            $administration = $community->administrations()->create([
                'started_at' => $data['started_at'],
                'ended_at' => $data['ended_at'],
            ]);

            $community->update(['current_administration_id' => $administration->id]);

            // Auto-assign the creator as President if they held that position previously
            if ($creator && $previousAdmin) {
                $presidentPosition = $community->positions()->where('name', 'President')->first();

                if ($presidentPosition) {
                    $wasPresident = $previousAdmin->members()
                        ->where('user_id', $creator->id)
                        ->where('position_id', $presidentPosition->id)
                        ->exists();

                    if ($wasPresident) {
                        $administration->members()->create([
                            'user_id' => $creator->id,
                            'position_id' => $presidentPosition->id,
                        ]);
                    }
                }
            }

            return $administration;
        });
    }
}
