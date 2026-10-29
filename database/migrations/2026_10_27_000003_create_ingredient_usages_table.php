<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_used', 14, 4);
            $table->date('used_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['used_at', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_usages');
    }
};
