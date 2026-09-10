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
            $table->string('cake_point_status', 20)->nullable()->after('assignment_sms_sent_at');
            $table->timestamp('cake_point_received_at')->nullable()->after('cake_point_status');
            $table->timestamp('cake_point_prepared_at')->nullable()->after('cake_point_received_at');
            $table->timestamp('cake_point_completed_at')->nullable()->after('cake_point_prepared_at');
            $table->foreignId('converted_to_sale_id')->nullable()->after('cake_point_completed_at')->constrained('sales')->nullOnDelete();
        });

        DB::table('sales')
            ->whereNotNull('assigned_to_user_id')
            ->whereNull('cake_point_status')
            ->update(['cake_point_status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_to_sale_id');
            $table->dropColumn([
                'cake_point_status',
                'cake_point_received_at',
                'cake_point_prepared_at',
                'cake_point_completed_at',
            ]);
        });
    }
};
