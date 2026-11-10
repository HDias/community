<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ContributionSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $community_id
 * @property float $monthly_amount
 * @property Carbon $effective_from
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['community_id', 'monthly_amount', 'effective_from'])]
class ContributionSetting extends Model
{
    /** @use HasFactory<ContributionSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'effective_from' => 'date',
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
     * Resolve the setting effective for the given reference month.
     *
     * A setting only applies once its effective date has arrived (today or
     * earlier) and it belongs to the reference month or a previous one.
     */
    public static function currentFor(
        Community $community,
        CarbonInterface $referenceMonth,
        ?CarbonInterface $asOf = null,
    ): ?self {
        $asOf = ($asOf ?? now())->copy()->endOfDay();
        $endOfReferenceMonth = $referenceMonth->copy()->endOfMonth();

        $cutoff = $asOf->lessThan($endOfReferenceMonth) ? $asOf : $endOfReferenceMonth;

        return $community->contributionSettings()
            ->where('effective_from', '<=', $cutoff)
            ->orderByDesc('effective_from')
            ->first();
    }
}
