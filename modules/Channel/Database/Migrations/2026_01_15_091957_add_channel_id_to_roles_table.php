<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the old unique constraint on name
            $table->dropUnique(['name']);
            
            // Add channel_id column (nullable for general roles)
            $table->unsignedBigInteger('channel_id')->nullable()->after('id');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            
            // Add unique constraint on name + channel_id
            // This allows same role name for different channels, and general roles (channel_id = null)
            $table->unique(['name', 'channel_id'], 'roles_name_channel_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('roles_name_channel_id_unique');
            
            // Drop foreign key and channel_id column
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
            
            // Restore the old unique constraint on name
            $table->unique('name');
        });
    }
};
