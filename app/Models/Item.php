<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    public const SALE_TYPE_RETAIL = 'retail';

    public const SALE_TYPE_ORDER = 'order';

    protected $fillable = [
        'name',
        'category_id',
        'package_unit_id',
        'price',
        'sale_type',
        'is_custom',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public static function saleTypeOptions(): array
    {
        return [
            self::SALE_TYPE_RETAIL => 'Retail (walk-in)',
            self::SALE_TYPE_ORDER => 'Order (customer + pickup)',
        ];
    }

    public function isOrderType(): bool
    {
        return $this->sale_type === self::SALE_TYPE_ORDER;
    }

    public function isCustom(): bool
    {
        return (bool) $this->is_custom;
    }

    public function formattedPrice(): string
    {
        if ($this->is_custom) {
            return 'Set on sale';
        }

        return number_format((float) $this->price, 0).' TZS';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function packageUnit(): BelongsTo
    {
        return $this->belongsTo(PackageUnit::class);
    }

    public function saleTypeLabel(): string
    {
        return self::saleTypeOptions()[$this->sale_type] ?? ucfirst((string) $this->sale_type);
    }
}
