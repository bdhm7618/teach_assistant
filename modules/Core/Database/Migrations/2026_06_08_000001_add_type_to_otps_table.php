<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('type', 30)->default('email_verification')->after('otpable_type');
            $table->index(['otpable_id', 'otpable_type', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex(['otpable_id', 'otpable_type', 'type']);
            $table->dropColumn('type');
        });
    }
};
