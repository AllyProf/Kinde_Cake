<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_LOGIN_FAILED = 'login_failed';

    public const ACTION_STAFF_CREATED = 'staff_created';

    public const ACTION_STAFF_UPDATED = 'staff_updated';

    public const ACTION_STAFF_DELETED = 'staff_deleted';

    public const ACTION_STAFF_PASSWORD_RESET = 'staff_password_reset';

    public const ACTION_PROFILE_UPDATED = 'profile_updated';

    public const ACTION_PASSWORD_CHANGED = 'password_changed';

    public const ACTION_IMPERSONATION_STARTED = 'impersonation_started';

    public const ACTION_IMPERSONATION_STOPPED = 'impersonation_stopped';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_LOGIN => 'Login',
            self::ACTION_LOGOUT => 'Logout',
            self::ACTION_LOGIN_FAILED => 'Login failed',
            self::ACTION_STAFF_CREATED => 'Staff created',
            self::ACTION_STAFF_UPDATED => 'Staff updated',
            self::ACTION_STAFF_DELETED => 'Staff deleted',
            self::ACTION_STAFF_PASSWORD_RESET => 'Password reset',
            self::ACTION_PROFILE_UPDATED => 'Profile updated',
            self::ACTION_PASSWORD_CHANGED => 'Password changed',
            self::ACTION_IMPERSONATION_STARTED => 'Impersonation started',
            self::ACTION_IMPERSONATION_STOPPED => 'Impersonation stopped',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /** @return array<string, string> */
    public static function actionOptions(): array
    {
        return [
            self::ACTION_LOGIN => 'Login',
            self::ACTION_LOGOUT => 'Logout',
            self::ACTION_LOGIN_FAILED => 'Login failed',
            self::ACTION_STAFF_CREATED => 'Staff created',
            self::ACTION_STAFF_UPDATED => 'Staff updated',
            self::ACTION_STAFF_DELETED => 'Staff deleted',
            self::ACTION_STAFF_PASSWORD_RESET => 'Password reset',
            self::ACTION_PROFILE_UPDATED => 'Profile updated',
            self::ACTION_PASSWORD_CHANGED => 'Password changed',
            self::ACTION_IMPERSONATION_STARTED => 'Impersonation started',
            self::ACTION_IMPERSONATION_STOPPED => 'Impersonation stopped',
        ];
    }
}
