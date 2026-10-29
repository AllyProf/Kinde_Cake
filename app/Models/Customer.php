<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'birthday',
        'birthday_wish_sent_year',
        'birthday_reminder_sent_year',
        'region',
        'district',
        'location',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function hasBirthdayToday(?\Carbon\CarbonInterface $today = null): bool
    {
        if (! $this->birthday) {
            return false;
        }

        $today ??= now();

        return (int) $this->birthday->month === (int) $today->month
            && (int) $this->birthday->day === (int) $today->day;
    }

    public function daysUntilBirthday(?\Carbon\CarbonInterface $today = null): ?int
    {
        if (! $this->birthday) {
            return null;
        }

        $today = ($today ?? now())->startOfDay();
        $next = $this->birthday->copy()->year($today->year)->startOfDay();

        if ($next->lt($today)) {
            $next->addYear();
        }

        return (int) $today->diffInDays($next);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function displayLabel(): string
    {
        if ($this->phone) {
            return "{$this->name} · {$this->phone}";
        }

        return $this->name;
    }
}
