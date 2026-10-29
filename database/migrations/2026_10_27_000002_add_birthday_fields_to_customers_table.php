<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('phone');
            $table->unsignedSmallInteger('birthday_wish_sent_year')->nullable()->after('birthday');
            $table->unsignedSmallInteger('birthday_reminder_sent_year')->nullable()->after('birthday_wish_sent_year');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'birthday',
                'birthday_wish_sent_year',
                'birthday_reminder_sent_year',
            ]);
        });
    }
};
