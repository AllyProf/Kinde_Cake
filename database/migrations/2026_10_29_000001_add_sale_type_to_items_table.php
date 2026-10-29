<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sale_type', 20)->default('retail')->after('price');
        });

        $cakeCategoryIds = DB::table('categories')
            ->where(function ($query) {
                $query->where('name', 'Cakes')
                    ->orWhere('name', 'like', '%cake%');
            })
            ->pluck('id');

        if ($cakeCategoryIds->isNotEmpty()) {
            DB::table('items')
                ->whereIn('category_id', $cakeCategoryIds)
                ->update(['sale_type' => 'order']);
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('sale_type');
        });
    }
};
