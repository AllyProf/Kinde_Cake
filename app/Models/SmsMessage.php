<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_CAKE_POINT = 'cake_point';

    public const RECIPIENT_MANUAL = 'manual';

    public const RECIPIENT_CUSTOMER = 'customer';

    public const RECIPIENT_STAFF = 'staff';

    protected $fillable = [
        'created_by_user_id',
        'recipient_phone',
        'recipient_name',
        'recipient_type',
        'recipient_id',
        'type',
        'message',
        'status',
        'scheduled_at',
        'sent_at',
        'provider',
        'provider_response',
        'last_error',
        'attempts',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isScheduled(): bool
    {
        return $this->isPending()
            && $this->scheduled_at
            && $this->scheduled_at->isFuture();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => $this->isScheduled() ? 'Scheduled' : 'Pending',
            self::STATUS_SENDING => 'Sending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'badge-success',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            self::STATUS_SENDING => 'badge-info',
            default => 'badge-warning',
        };
    }

    public function recipientLabel(): string
    {
        if ($this->recipient_name) {
            return $this->recipient_name.' · '.$this->recipient_phone;
        }

        return $this->recipient_phone;
    }

    public function messagePreview(int $length = 60): string
    {
        return str($this->message)->limit($length)->toString();
    }
}
