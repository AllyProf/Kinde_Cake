<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('notes');
            $table->timestamp('cancelled_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_receivings', function (Blueprint $table) {
            $table->dropColumn(['status', 'cancelled_at']);
        });
    }
};
