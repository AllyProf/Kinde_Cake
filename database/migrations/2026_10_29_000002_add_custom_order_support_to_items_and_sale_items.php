<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('sale_type');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('item_name')->nullable()->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('item_name');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
