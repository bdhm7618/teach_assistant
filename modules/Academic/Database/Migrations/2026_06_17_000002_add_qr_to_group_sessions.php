<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('group_sessions', 'qr_token')) {
                $table->string('qr_token', 191)->nullable()->unique()->after('notes');
            } else {
                // Column exists from a previous partial run — shrink it so the unique index fits, then add it
                $table->string('qr_token', 191)->nullable()->change();
                $table->unique('qr_token');
            }
            if (! Schema::hasColumn('group_sessions', 'qr_expires_at')) {
                $table->dateTime('qr_expires_at')->nullable()->after('qr_token');
            }
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
