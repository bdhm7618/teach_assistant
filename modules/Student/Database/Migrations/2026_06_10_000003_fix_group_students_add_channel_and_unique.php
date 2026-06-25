<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_students', function (Blueprint $table) {
            // Add channel_id for multi-tenant consistency (matches group_users pattern)
            $table->foreignId('channel_id')
                ->nullable()
                ->after('id')
                ->constrained('channels')
                ->cascadeOnDelete();

            // Prevent duplicate enrollment of the same student in the same group
            $table->unique(['group_id', 'student_id'], 'group_students_group_student_unique');

            $table->index(['channel_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('group_students', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropIndex(['channel_id', 'student_id']);
            $table->dropUnique('group_students_group_student_unique');
            $table->dropColumn('channel_id');
        });
    }
};
