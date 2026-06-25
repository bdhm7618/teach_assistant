<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop global unique constraints — they break multi-tenancy
            // (two channels can't share a student with the same code/email/phone)
            $table->dropUnique('students_code_unique');
            $table->dropUnique('students_email_unique');
            $table->dropUnique('students_phone_unique');

            // Replace with per-channel unique constraints
            $table->unique(['code', 'channel_id'], 'students_code_channel_unique');
            $table->unique(['email', 'channel_id'], 'students_email_channel_unique');
            $table->unique(['phone', 'channel_id'], 'students_phone_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_code_channel_unique');
            $table->dropUnique('students_email_channel_unique');
            $table->dropUnique('students_phone_channel_unique');

            $table->unique('code');
            $table->unique('email');
            $table->unique('phone');
        });
    }
};
