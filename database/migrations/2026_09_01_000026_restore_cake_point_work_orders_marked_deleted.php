<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales')
            ->whereNotNull('cake_point_status')
            ->where('status', 'deleted')
            ->update([
                'status' => 'pending',
                'deleted_at' => null,
            ]);
    }

    public function down(): void
    {
        // No rollback — previous deleted state was incorrect for cake point work orders.
    }
};
