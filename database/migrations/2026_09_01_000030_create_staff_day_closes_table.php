<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_day_closes', function (Blueprint $table) {
            $table->id();
            $table->date('business_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('closed_at');
            $table->text('notes')->nullable();
            $table->json('summary');
            $table->timestamps();

            $table->unique(['business_date', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_day_closes');
    }
};
