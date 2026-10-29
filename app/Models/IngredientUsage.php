<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientUsage extends Model
{
    protected $fillable = [
        'ingredient_id',
        'user_id',
        'quantity_used',
        'used_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:4',
            'used_at' => 'date',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedQuantity(): string
    {
        $symbol = $this->ingredient?->usagePackageUnit?->symbol ?? '';

        return trim(number_format((float) $this->quantity_used, 0).' '.$symbol);
    }
}
