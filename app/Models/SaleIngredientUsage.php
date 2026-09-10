<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleIngredientUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'ingredient_id',
        'quantity_used',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
