<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total');
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20);
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('credit_repayment_date')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        if (Schema::hasTable('sales')) {
            DB::table('sales')->where('status', 'paid')->update([
                'amount_paid' => DB::raw('total'),
            ]);

            $paidSales = DB::table('sales')
                ->where('status', 'paid')
                ->whereNotNull('payment_method')
                ->get();

            foreach ($paidSales as $sale) {
                DB::table('sale_payments')->insert([
                    'sale_id' => $sale->id,
                    'user_id' => $sale->user_id,
                    'amount' => $sale->total,
                    'payment_method' => $sale->payment_method,
                    'payment_provider_id' => $sale->payment_provider_id,
                    'payment_reference' => $sale->payment_reference,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer_name,
                    'customer_phone' => $sale->customer_phone,
                    'credit_repayment_date' => $sale->credit_repayment_date,
                    'paid_at' => $sale->paid_at ?? $sale->sold_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('sales')
                ->where('amount_paid', '>', 0)
                ->whereColumn('amount_paid', '<', 'total')
                ->update(['status' => 'partial']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
