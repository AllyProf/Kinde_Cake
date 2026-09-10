<?php

use App\Models\Sale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales')
            ->where('amount_paid', 0)
            ->where('status', Sale::STATUS_PARTIAL)
            ->whereNull('cake_point_status')
            ->update(['status' => Sale::STATUS_PENDING]);
    }

    public function down(): void
    {
        // Non-reversible data correction.
    }
};
