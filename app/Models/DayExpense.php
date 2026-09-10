<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayExpense extends Model
{
    public const CATEGORY_FOOD = 'food';

    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORY_SUPPLIES = 'supplies';

    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'business_date',
        'category',
        'description',
        'amount',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0).' TZS';
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? ucfirst($this->category);
    }

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_FOOD => 'Food',
            self::CATEGORY_TRANSPORT => 'Transport',
            self::CATEGORY_SUPPLIES => 'Supplies',
            self::CATEGORY_OTHER => 'Other',
        ];
    }
}
