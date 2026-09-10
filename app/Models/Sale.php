<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DELETED = 'deleted';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_MOBILE = 'mobile';

    public const PAYMENT_BANK = 'bank';

    public const PAYMENT_CREDIT = 'credit';

    public const CAKE_POINT_SENT = 'sent';

    public const CAKE_POINT_RECEIVED = 'received';

    public const CAKE_POINT_PREPARED = 'prepared';

    public const CAKE_POINT_COMPLETED = 'completed';

    protected $fillable = [
        'sale_number',
        'user_id',
        'assigned_to_user_id',
        'assigned_by_user_id',
        'assigned_at',
        'assignment_sms_sent_at',
        'cake_point_status',
        'cake_point_received_at',
        'cake_point_prepared_at',
        'cake_point_completed_at',
        'converted_to_sale_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'total',
        'amount_paid',
        'status',
        'payment_method',
        'payment_provider_id',
        'payment_reference',
        'credit_repayment_date',
        'paid_at',
        'cancelled_at',
        'deleted_at',
        'notes',
        'sold_at',
        'edited_at',
        'order_sms_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'sold_at' => 'datetime',
            'paid_at' => 'datetime',
            'credit_repayment_date' => 'date',
            'cancelled_at' => 'datetime',
            'deleted_at' => 'datetime',
            'edited_at' => 'datetime',
            'order_sms_sent_at' => 'datetime',
            'assigned_at' => 'datetime',
            'assignment_sms_sent_at' => 'datetime',
            'cake_point_received_at' => 'datetime',
            'cake_point_prepared_at' => 'datetime',
            'cake_point_completed_at' => 'datetime',
        ];
    }

    public function convertedToSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentProvider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function ingredientUsages(): HasMany
    {
        return $this->hasMany(SaleIngredientUsage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class)->latest('paid_at')->latest('id');
    }

    public function formattedTotal(): string
    {
        return number_format((float) $this->total, 0).' TZS';
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function isVoided(): bool
    {
        return $this->isCancelled() || $this->isDeleted();
    }

    public function canBeEdited(): bool
    {
        return $this->isPending() && (float) $this->amount_paid <= 0;
    }

    public function canBePaid(): bool
    {
        return $this->isPending() || $this->isPartiallyPaid();
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function formattedAmountPaid(): string
    {
        return number_format((float) $this->amount_paid, 0).' TZS';
    }

    public function formattedBalanceDue(): string
    {
        return number_format($this->balanceDue(), 0).' TZS';
    }

    public function hasOutstandingBalance(): bool
    {
        return $this->balanceDue() > 0;
    }

    public function hasOpenDebt(): bool
    {
        return ! $this->isVoided() && $this->hasOutstandingBalance();
    }

    public function debtAmount(): float
    {
        if ($this->isVoided()) {
            return 0;
        }

        return $this->balanceDue();
    }

    public function formattedDebtAmount(): string
    {
        return number_format($this->debtAmount(), 0).' TZS';
    }

    public function isDebtOverdue(): bool
    {
        if (! $this->credit_repayment_date || ! $this->hasOpenDebt()) {
            return false;
        }

        return $this->credit_repayment_date->isPast();
    }

    public function debtTypeLabel(): string
    {
        if ($this->isCreditPayment()) {
            return 'Credit';
        }

        if ($this->isPartiallyPaid()) {
            return 'Partial';
        }

        return 'Unpaid';
    }

    public function debtTypeBadgeClass(): string
    {
        return match ($this->debtTypeLabel()) {
            'Credit' => 'badge-warning',
            'Partial' => 'badge-info',
            default => 'badge-secondary',
        };
    }

    public function scopeWithOpenDebt($query)
    {
        return $query->realSales()
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_DELETED])
            ->whereColumn('amount_paid', '<', 'total');
    }

    public function scopeRealSales(Builder $query): Builder
    {
        return $query->whereNull('cake_point_status');
    }

    public function isCakePointWorkOrder(): bool
    {
        return filled($this->cake_point_status);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('user_id', $user->id)
                ->orWhere('assigned_to_user_id', $user->id);
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $this->user_id === $user->id || $this->assigned_to_user_id === $user->id;
    }

    public function isAssigned(): bool
    {
        return $this->assigned_to_user_id !== null;
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to_user_id === $user->id;
    }

    public function isCakePointOrder(): bool
    {
        return filled($this->cake_point_status);
    }

    public function isCakePointSent(): bool
    {
        return $this->cake_point_status === self::CAKE_POINT_SENT;
    }

    public function isCakePointReceived(): bool
    {
        return $this->cake_point_status === self::CAKE_POINT_RECEIVED;
    }

    public function isCakePointPrepared(): bool
    {
        return $this->cake_point_status === self::CAKE_POINT_PREPARED;
    }

    public function isCakePointCompleted(): bool
    {
        return $this->cake_point_status === self::CAKE_POINT_COMPLETED;
    }

    public function canUpdateCakePointStatus(User $user): bool
    {
        if ($this->isCakePointCompleted() || $this->isVoided() || ! $this->isAssigned()) {
            return false;
        }

        return $this->isAssignedTo($user);
    }

    public function canConvertToSale(User $user): bool
    {
        return $this->isCakePointPrepared()
            && ! $this->converted_to_sale_id
            && ! $this->isVoided()
            && $this->isAssignedTo($user);
    }

    public function cakePointStatusLabel(): string
    {
        return match ($this->cake_point_status) {
            self::CAKE_POINT_SENT => 'Sent',
            self::CAKE_POINT_RECEIVED => 'Received',
            self::CAKE_POINT_PREPARED => 'Prepared',
            self::CAKE_POINT_COMPLETED => 'Completed',
            default => '—',
        };
    }

    public function cakePointStatusBadgeClass(): string
    {
        return match ($this->cake_point_status) {
            self::CAKE_POINT_SENT => 'badge-warning',
            self::CAKE_POINT_RECEIVED => 'badge-info',
            self::CAKE_POINT_PREPARED => 'badge-primary',
            self::CAKE_POINT_COMPLETED => 'badge-success',
            default => 'badge-secondary',
        };
    }

    /** @return array<string, string> */
    public static function cakePointStatusFilterOptions(): array
    {
        return [
            '' => 'All order statuses',
            self::CAKE_POINT_SENT => 'Sent',
            self::CAKE_POINT_RECEIVED => 'Received',
            self::CAKE_POINT_PREPARED => 'Prepared',
            self::CAKE_POINT_COMPLETED => 'Completed',
        ];
    }

    public function canBePaidBy(User $user): bool
    {
        return $this->canBePaid()
            && ! $this->isCakePointWorkOrder()
            && $user->canPaySales();
    }

    public function wasEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function hasCustomerPhone(): bool
    {
        return filled($this->customer_phone);
    }

    public function orderSmsWasSent(): bool
    {
        return $this->assignment_sms_sent_at !== null;
    }

    public function statusLabel(): string
    {
        if ($this->isPaid()) {
            return 'Paid';
        }

        if ((float) $this->amount_paid > 0 && $this->hasOutstandingBalance()) {
            return $this->isCreditPayment() ? 'Credit' : 'Partial';
        }

        return match ($this->status) {
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_DELETED => 'Deleted',
            default => 'Pending',
        };
    }

    public function statusBadgeClass(): string
    {
        if ($this->isPaid()) {
            return 'badge-success';
        }

        if ((float) $this->amount_paid > 0 && $this->hasOutstandingBalance()) {
            return $this->isCreditPayment() ? 'badge-warning' : 'badge-info';
        }

        return match ($this->status) {
            self::STATUS_PARTIAL => 'badge-info',
            self::STATUS_CANCELLED => 'badge-secondary',
            self::STATUS_DELETED => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function paymentSummaryLabel(): string
    {
        if ($this->isPaid()) {
            return $this->payments->first()?->paymentMethodLabel()
                ?? ($this->payment_method ? $this->paymentMethodLabel() : 'Paid in full');
        }

        if ($this->isCreditPayment() && $this->hasOutstandingBalance()) {
            return 'Credit · Due '.$this->formattedBalanceDue();
        }

        if ($this->isPartiallyPaid()) {
            return 'Paid '.$this->formattedAmountPaid().' · Due '.$this->formattedBalanceDue();
        }

        return '—';
    }

    public function hasPaymentRecorded(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->isNotEmpty();
        }

        return $this->payments()->exists();
    }

    public function paidByName(): ?string
    {
        $payment = $this->relationLoaded('payments')
            ? $this->payments->first()
            : $this->payments()->with('user')->first();

        return $payment?->user?->name;
    }

    public function paymentMethodLabel(): string
    {
        if (! $this->payment_method) {
            return '—';
        }

        $label = match ($this->payment_method) {
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_MOBILE => 'Mobile payment',
            self::PAYMENT_BANK => 'Bank payment',
            self::PAYMENT_CREDIT => 'Credit',
            default => ucfirst($this->payment_method),
        };

        if ($this->paymentProvider) {
            return $label.' · '.$this->paymentProvider->name;
        }

        return $label;
    }

    public function isCreditPayment(): bool
    {
        return $this->payment_method === self::PAYMENT_CREDIT;
    }

    public function itemsSummary(): string
    {
        return $this->items
            ->map(function (SaleItem $line) {
                $name = $line->item?->name ?? 'Item';
                $qty = number_format((float) $line->quantity, 0);

                return "{$name} x{$qty}";
            })
            ->join(', ');
    }

    /** @return array<string, string> */
    public static function statusFilterOptions(): array
    {
        return [
            '' => 'All statuses',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_PAID => 'Paid',
            self::STATUS_DELETED => 'Deleted',
        ];
    }

    /** @return array<string, string> */
    public static function paymentMethodOptions(): array
    {
        return [
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_MOBILE => 'Mobile payment',
            self::PAYMENT_BANK => 'Bank payment',
            self::PAYMENT_CREDIT => 'Credit',
        ];
    }
}
