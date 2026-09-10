<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_STAFF = 'staff';

    public const IMPERSONATOR_SESSION_KEY = 'impersonator_id';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'photo',
        'password',
        'role',
        'staff_role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'staff_role_id');
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public static function isImpersonating(): bool
    {
        return session()->has(self::IMPERSONATOR_SESSION_KEY);
    }

    public static function impersonator(): ?self
    {
        $id = session(self::IMPERSONATOR_SESSION_KEY);

        return $id ? self::query()->find($id) : null;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function roleLabel(): string
    {
        if ($this->isOwner()) {
            return 'Owner';
        }

        return $this->staffRole?->name ?? 'Staff';
    }

    public function initials(): string
    {
        $initials = '';

        foreach (explode(' ', trim($this->name)) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return substr($initials, 0, 2) ?: '?';
    }

    public function hasPhoto(): bool
    {
        return filled($this->photo) && Storage::disk('public')->exists($this->photo);
    }

    public function photoUrl(): ?string
    {
        if (! $this->hasPhoto()) {
            return null;
        }

        $path = '/storage/'.ltrim(str_replace('\\', '/', $this->photo), '/');
        $version = $this->updated_at?->timestamp;

        return $version ? $path.'?v='.$version : $path;
    }

    public function hasPermission(string $key): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->staffRole?->hasPermission($key) ?? false;
    }

    public function canPaySales(): bool
    {
        return $this->hasPermission('orders.manage') || $this->hasPermission('orders.create');
    }

    public function canCloseDay(): bool
    {
        return $this->canCloseStaffDay() || $this->canCloseBusinessDay();
    }

    public function canCloseStaffDay(): bool
    {
        return $this->isStaff()
            && ($this->hasPermission('orders.create') || $this->hasPermission('orders.manage'));
    }

    public function canClosePersonalDay(): bool
    {
        return $this->canCloseStaffDay() || $this->canCloseBusinessDay();
    }

    public function canCloseBusinessDay(): bool
    {
        return $this->isOwner();
    }

    public function canViewSms(): bool
    {
        return $this->isOwner() || $this->hasPermission('sms.view');
    }

    public function canSendSms(): bool
    {
        return $this->isOwner() || $this->hasPermission('sms.send');
    }
}
