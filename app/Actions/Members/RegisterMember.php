<?php

namespace App\Actions\Members;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use App\Notifications\MemberRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterMember
{
    /**
     * Create a user with profile, attach to community, and send notification.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Community $community, array $data): User
    {
        $password = Str::password(12);

        $user = DB::transaction(function () use ($community, $data, $password) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $password,
            ]);

            $user->profile()->create([
                'social_name' => $data['social_name'] ?? null,
                'nickname' => $data['nickname'] ?? null,
                'cpf' => preg_replace('/\D/', '', $data['cpf']),
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'] ?? null,
                'profession' => $data['profession'] ?? null,
                'address_street' => $data['address_street'] ?? null,
                'address_number' => $data['address_number'] ?? null,
                'address_neighborhood' => $data['address_neighborhood'] ?? null,
                'address_city' => $data['address_city'] ?? null,
                'address_state' => $data['address_state'] ?? null,
                'address_zip' => $data['address_zip'] ?? null,
            ]);

            $community->members()->attach($user, [
                'role' => CommunityRole::Member->value,
                'joined_at' => now(),
            ]);

            return $user;
        });

        $user->notify(new MemberRegistered($community, $password));

        return $user;
    }
}
