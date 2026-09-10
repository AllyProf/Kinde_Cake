<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'user_id',
        'amount',
        'payment_method',
        'payment_provider_id',
        'payment_reference',
        'customer_id',
        'customer_name',
        'customer_phone',
        'credit_repayment_date',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'credit_repayment_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentProvider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class);
    }

    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0).' TZS';
    }

    public function paymentMethodLabel(): string
    {
        $label = match ($this->payment_method) {
            Sale::PAYMENT_CASH => 'Cash',
            Sale::PAYMENT_MOBILE => 'Mobile payment',
            Sale::PAYMENT_BANK => 'Bank payment',
            Sale::PAYMENT_CREDIT => 'Credit',
            default => ucfirst($this->payment_method),
        };

        if ($this->paymentProvider) {
            return $label.' · '.$this->paymentProvider->name;
        }

        return $label;
    }
}
