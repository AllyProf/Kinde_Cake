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
            $table->string('status', 20)->default('pending')->after('total');
            $table->string('payment_method', 20)->nullable()->after('status');
            $table->foreignId('payment_provider_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('payment_provider_id');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
        });

        if (DB::table('sales')->exists()) {
            DB::table('sales')->update([
                'status' => 'paid',
                'payment_method' => 'cash',
                'paid_at' => DB::raw('sold_at'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_provider_id');
            $table->dropColumn(['status', 'payment_method', 'paid_at', 'cancelled_at']);
        });
    }
};
