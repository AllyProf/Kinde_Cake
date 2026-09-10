<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->decimal('purchase_cost', 14, 2)->default(0)->after('usage_per_receiving');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->dropColumn('purchase_cost');
        });
    }
};
