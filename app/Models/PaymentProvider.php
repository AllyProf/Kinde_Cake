<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends Model
{
    public const TYPE_MOBILE = 'mobile';

    public const TYPE_BANK = 'bank';

    protected $fillable = [
        'name',
        'type',
        'is_builtin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MOBILE => 'Mobile payment',
            self::TYPE_BANK => 'Bank payment',
            default => ucfirst($this->type),
        };
    }
}
