<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_sessions', function (Blueprint $table) {
            // Signed QR token stored so it can be invalidated by clearing it
            $table->string('qr_token', 500)->nullable()->unique()->after('notes');
            // QR expires at scheduled_at + duration_minutes + 30 min grace period
            $table->dateTime('qr_expires_at')->nullable()->after('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('group_sessions', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'qr_expires_at']);
        });
    }
};
