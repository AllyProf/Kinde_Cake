<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('receiving_package_unit_id')->constrained('package_units')->restrictOnDelete();
            $table->foreignId('usage_package_unit_id')->constrained('package_units')->restrictOnDelete();
            $table->decimal('usage_per_receiving', 12, 4);
            $table->decimal('stock_quantity', 14, 4)->default(0);
            $table->decimal('reorder_level', 12, 4)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ingredient_receivings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('usage_quantity_added', 14, 4);
            $table->decimal('usage_per_receiving', 12, 4);
            $table->date('received_at');
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_receivings');
        Schema::dropIfExists('ingredients');
    }
};
