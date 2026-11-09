<?php

namespace App\Models;

use App\Enums\ContributionType;
use App\Enums\DonorType;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $community_id
 * @property int|null $user_id
 * @property Carbon $reference_month
 * @property Carbon|null $monthly_key
 * @property float $amount
 * @property int $registered_by
 * @property ContributionType $type
 * @property DonorType|null $donor_type
 * @property string|null $donor_name
 * @property string|null $donor_document
 * @property string|null $notes
 * @property Carbon|null $reversed_at
 * @property int|null $reversed_by
 * @property string|null $reversal_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['community_id', 'user_id', 'reference_month', 'monthly_key', 'amount', 'registered_by', 'type', 'donor_type', 'donor_name', 'donor_document', 'notes', 'reversed_at', 'reversed_by', 'reversal_reason'])]
class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_month' => 'date',
            'monthly_key' => 'date',
            'amount' => 'decimal:2',
            'type' => ContributionType::class,
            'donor_type' => DonorType::class,
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Community, $this>
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
