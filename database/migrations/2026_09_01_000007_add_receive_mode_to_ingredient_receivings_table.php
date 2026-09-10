<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->string('receive_mode', 10)->default('package')->after('ingredient_id');
            $table->decimal('entry_quantity', 12, 4)->nullable()->after('quantity_received');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->dropColumn(['receive_mode', 'entry_quantity']);
        });
    }
};
