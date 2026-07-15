<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $canManageCommunity = false;
        $currentPosition = null;

        if ($user) {
            $community = $user->currentCommunity;

            $canManageCommunity = $user->is_admin
                || ($community && $user->hasExecutivePositionIn($community));

            if ($community && $community->currentAdministration) {
                $member = $community->currentAdministration->members()
                    ->where('user_id', $user->id)
                    ->with('position')
                    ->first();

                $currentPosition = $member?->position?->name;
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'canManageCommunity' => $canManageCommunity,
            'currentPosition' => $currentPosition,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
