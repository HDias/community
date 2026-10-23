<?php

namespace App\Models;

use Database\Factories\ContributionReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $community_id
 * @property Carbon $reference_month
 * @property int $total_members
 * @property int $total_paid
 * @property int $total_defaulting
 * @property float $total_collected
 * @property Carbon $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['community_id', 'reference_month', 'total_members', 'total_paid', 'total_defaulting', 'total_collected', 'generated_at'])]
class ContributionReport extends Model
{
    /** @use HasFactory<ContributionReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_month' => 'date',
            'total_collected' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Community, $this>
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
