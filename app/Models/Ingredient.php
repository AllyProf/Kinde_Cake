<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'receiving_package_unit_id',
        'usage_package_unit_id',
        'usage_per_receiving',
        'stock_quantity',
        'reorder_level',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'usage_per_receiving' => 'decimal:4',
            'stock_quantity' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function receivingPackageUnit(): BelongsTo
    {
        return $this->belongsTo(PackageUnit::class, 'receiving_package_unit_id');
    }

    public function usagePackageUnit(): BelongsTo
    {
        return $this->belongsTo(PackageUnit::class, 'usage_package_unit_id');
    }

    public function receivings(): HasMany
    {
        return $this->hasMany(IngredientReceiving::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock_quantity <= (float) $this->reorder_level;
    }

    public function conversionLabel(): string
    {
        $usage = $this->usagePackageUnit?->symbol ?? 'unit';
        $receiving = $this->receivingPackageUnit?->symbol ?? 'unit';

        return number_format((float) $this->usage_per_receiving, 0).' '.$usage.' / '.$receiving;
    }

    public function formattedStock(): string
    {
        $symbol = $this->usagePackageUnit?->symbol ?? '';

        return number_format((float) $this->stock_quantity, 0).' '.$symbol;
    }

    public function stockInReceivingUnits(): float
    {
        $perReceiving = (float) $this->usage_per_receiving;

        if ($perReceiving <= 0) {
            return 0;
        }

        return round((float) $this->stock_quantity / $perReceiving, 2);
    }

    public function formattedStockInReceiving(): string
    {
        $symbol = $this->receivingPackageUnit?->symbol ?? '';

        return number_format($this->stockInReceivingUnits(), 1).' '.$symbol;
    }

    public function stockStatusLabel(): string
    {
        if ((float) $this->stock_quantity <= 0) {
            return 'Out of stock';
        }

        if ($this->isLowStock()) {
            return 'Low stock';
        }

        return 'In stock';
    }

    public function stockStatusClass(): string
    {
        if ((float) $this->stock_quantity <= 0) {
            return 'danger';
        }

        if ($this->isLowStock()) {
            return 'warning';
        }

        return 'success';
    }

    public function calculateUsageFromReceiving(float $receivingQuantity): float
    {
        return round($receivingQuantity * (float) $this->usage_per_receiving, 4);
    }
}
