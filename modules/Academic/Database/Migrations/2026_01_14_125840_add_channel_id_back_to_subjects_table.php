<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'channel_id')) {
                // Get existing indexes
                $indexes = DB::select("SHOW INDEXES FROM subjects WHERE Key_name != 'PRIMARY'");
                foreach ($indexes as $index) {
                    if (str_contains($index->Key_name, 'code')) {
                        try {
                            DB::statement("ALTER TABLE subjects DROP INDEX {$index->Key_name}");
                        } catch (\Exception $e) {
                            // Ignore if doesn't exist
                        }
                    }
                }
                
                // Add channel_id as nullable (for general subjects)
                $table->foreignId('channel_id')->nullable()->after('is_active');
                $table->foreign('channel_id')->references('id')->on('channels')->cascadeOnDelete();
                
                // Add unique constraint on code and channel_id (allowing null channel_id for general subjects)
                $table->unique(['code', 'channel_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'channel_id')) {
                // Drop unique constraint
                try {
                    $table->dropUnique(['subjects_code_channel_id_unique']);
                } catch (\Exception $e) {
                    // Ignore
                }
                
                // Drop foreign key
                try {
                    $table->dropForeign(['subjects_channel_id_foreign']);
                } catch (\Exception $e) {
                    // Ignore
                }
                
                // Drop column
                $table->dropColumn('channel_id');
                
                // Restore unique constraint on code
                $table->unique('code');
            }
        });
    }
};
