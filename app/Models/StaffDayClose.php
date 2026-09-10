<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDayClose extends Model
{
    protected $fillable = [
        'business_date',
        'user_id',
        'closed_at',
        'notes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'closed_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedBusinessDate(): string
    {
        return $this->business_date->format('d M Y');
    }

    /** @param  array<string, mixed>  $summary */
    public function summaryValue(string $key, mixed $default = 0): mixed
    {
        return data_get($this->summary, $key, $default);
    }

    public function formattedSummaryMoney(string $key): string
    {
        return number_format((float) $this->summaryValue($key, 0), 0).' TZS';
    }
}
