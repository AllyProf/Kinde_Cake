<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->string('recipient_phone', 20);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_type', 30)->default('manual');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('type', 30)->default('manual');
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('provider', 20)->nullable();
            $table->text('provider_response')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->nullableMorphs('related');
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
