<?php

namespace App\Models;

use Database\Factories\AdministrationMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $administration_id
 * @property int $user_id
 * @property int $position_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['administration_id', 'user_id', 'position_id'])]
class AdministrationMember extends Model
{
    /** @use HasFactory<AdministrationMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Administration, $this>
     */
    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
