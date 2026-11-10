<?php

namespace App\Concerns;

use App\Models\Community;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasCommunities
{
    /**
     * Positions allowed to register payments/donations and reverse contributions.
     *
     * @var list<string>
     */
    public const CONTRIBUTION_OFFICER_POSITIONS = ['President', 'Vice-President', 'Treasurer'];

    /**
     * Determine if the user holds a position with admin access in the community's current administration.
     */
    public function hasExecutivePositionIn(Community $community): bool
    {
        $administration = $community->currentAdministration;

        if (! $administration) {
            return false;
        }

        return $administration->members()
            ->where('user_id', $this->id)
            ->whereHas('position', fn ($q) => $q->where('has_admin_access', true))
            ->exists();
    }

    /**
     * Determine if the user holds a contribution officer position in the community's current administration.
     */
    public function hasContributionOfficerPositionIn(Community $community): bool
    {
        $administration = $community->currentAdministration;

        if (! $administration) {
            return false;
        }

        return $administration->members()
            ->where('user_id', $this->id)
            ->whereHas('position', fn ($q) => $q->whereIn('name', self::CONTRIBUTION_OFFICER_POSITIONS))
            ->exists();
    }

    /**
     * Determine if the user holds a leadership position (President or Secretary) in the community's current administration.
     */
    public function hasLeadershipPositionIn(Community $community): bool
    {
        $administration = $community->currentAdministration;

        if (! $administration) {
            return false;
        }

        return $administration->members()
            ->where('user_id', $this->id)
            ->whereHas('position', fn ($q) => $q->where('has_admin_access', true))
            ->exists();
    }

    /**
     * @return BelongsToMany<Community, $this>
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Community, $this>
     */
    public function currentCommunity(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'current_community_id');
    }

    public function switchCommunity(Community $community): void
    {
        $this->forceFill(['current_community_id' => $community->id])->save();
        $this->setRelation('currentCommunity', $community);
    }

    public function belongsToCommunity(Community $community): bool
    {
        return $this->communities()->where('community_id', $community->id)->exists();
    }
}
