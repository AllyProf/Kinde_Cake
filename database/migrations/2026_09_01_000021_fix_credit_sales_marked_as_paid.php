<?php

use App\Models\Sale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Sale::query()
            ->where('payment_method', Sale::PAYMENT_CREDIT)
            ->where('status', Sale::STATUS_PAID)
            ->whereColumn('amount_paid', '>=', 'total')
            ->orderBy('id')
            ->each(function (Sale $sale) {
                $cashPaid = (float) DB::table('sale_payments')
                    ->where('sale_id', $sale->id)
                    ->where('payment_method', '!=', Sale::PAYMENT_CREDIT)
                    ->sum('amount');

                $isFullyPaid = $cashPaid >= (float) $sale->total;

                $sale->update([
                    'amount_paid' => round($cashPaid, 2),
                    'status' => $isFullyPaid ? Sale::STATUS_PAID : Sale::STATUS_PARTIAL,
                    'paid_at' => $isFullyPaid ? $sale->paid_at : null,
                ]);
            });
    }

    public function down(): void
    {
        // Non-reversible data correction.
    }
};
