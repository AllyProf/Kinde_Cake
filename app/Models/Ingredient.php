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
        return $this->formatQty((float) $this->stock_quantity).' '.($this->usagePackageUnit?->symbol ?? 'unit');
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
        return $this->formatQty($this->stockInReceivingUnits()).' '.($this->receivingPackageUnit?->symbol ?? 'unit');
    }

    /**
     * Stock as both receiving packages and usage units, e.g. "2 tray (60 pcs)".
     */
    public function formattedStockSummary(): string
    {
        $receivingSymbol = $this->receivingPackageUnit?->symbol ?? 'unit';
        $usageSymbol = $this->usagePackageUnit?->symbol ?? 'unit';
        $receivingQty = $this->stockInReceivingUnits();
        $usageQty = (float) $this->stock_quantity;

        if ($receivingSymbol === $usageSymbol || (float) $this->usage_per_receiving === 1.0) {
            return $this->formatQty($usageQty).' '.$usageSymbol;
        }

        return $this->formatQty($receivingQty).' '.$receivingSymbol
            .' ('.$this->formatQty($usageQty).' '.$usageSymbol.')';
    }

    private function formatQty(float $value): string
    {
        if (abs($value - round($value)) < 0.0001) {
            return number_format(round($value), 0);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
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
