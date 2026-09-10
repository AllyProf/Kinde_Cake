<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closes', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->foreignId('closed_by_user_id')->constrained('users');
            $table->timestamp('closed_at');
            $table->text('notes')->nullable();
            $table->json('summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closes');
    }
};
