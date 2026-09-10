<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientReceiving extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ingredient_id',
        'receive_mode',
        'user_id',
        'quantity_received',
        'entry_quantity',
        'usage_quantity_added',
        'usage_per_receiving',
        'purchase_cost',
        'received_at',
        'supplier',
        'notes',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:4',
            'usage_quantity_added' => 'decimal:4',
            'usage_per_receiving' => 'decimal:4',
            'purchase_cost' => 'decimal:2',
            'received_at' => 'date',
            'cancelled_at' => 'datetime',
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

    public function entryUnitSymbol(): string
    {
        if ($this->receive_mode === 'usage') {
            return $this->ingredient?->usagePackageUnit?->symbol ?? '';
        }

        return $this->ingredient?->receivingPackageUnit?->symbol ?? '';
    }

    public function formattedEntry(): string
    {
        $qty = number_format((float) ($this->entry_quantity ?? $this->quantity_received), 0);

        return trim($qty.' '.$this->entryUnitSymbol());
    }

    public function formattedPurchaseCost(): string
    {
        return number_format((float) $this->purchase_cost, 0).' TZS';
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        $ingredient = $this->relationLoaded('ingredient')
            ? $this->ingredient
            : $this->ingredient()->first();

        if (! $ingredient) {
            return false;
        }

        return (float) $ingredient->stock_quantity >= (float) $this->usage_quantity_added;
    }

    public function statusLabel(): string
    {
        return $this->isCancelled() ? 'Cancelled' : 'Active';
    }

    public function statusBadgeClass(): string
    {
        return $this->isCancelled() ? 'badge-secondary' : 'badge-success';
    }
}
