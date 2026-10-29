<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'item_id',
        'item_name',
        'quantity',
        'unit_price',
        'discount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function displayName(): string
    {
        if (filled($this->item_name)) {
            return (string) $this->item_name;
        }

        return $this->item?->name ?? 'Item';
    }
}
