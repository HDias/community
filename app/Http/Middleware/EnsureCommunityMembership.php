<?php

namespace App\Http\Middleware;

use App\Models\Community;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommunityMembership
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('current_community');

        if (! $slug) {
            return $next($request);
        }

        $community = Community::where('slug', $slug)->first();

        if (! $community) {
            abort(404);
        }

        $user = $request->user();

        if (! $user || ! $user->belongsToCommunity($community)) {
            abort(403);
        }

        $request->attributes->set('community', $community);

        return $next($request);
    }
}
