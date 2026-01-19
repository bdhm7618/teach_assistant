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
        Schema::table('attendances', function (Blueprint $table) {
            // Drop the old unique constraint
            // We'll handle uniqueness in application logic using whereDate() to allow multiple records per day
            $table->dropUnique('unique_attendance_record');
            
            // Change date column to datetime
            $table->dateTime('date')->change();
            
            // Note: We removed the unique constraint because we want to allow multiple attendance records
            // per day (e.g., morning and afternoon sessions). Uniqueness is now enforced in application
            // logic via AttendanceRequest validation which uses whereDate() to check for duplicates.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Change datetime back to date
            $table->date('date')->change();
            
            // Recreate the old unique constraint (date-based uniqueness)
            $table->unique(['student_id', 'group_id', 'date', 'channel_id'], 'unique_attendance_record');
        });
    }
};
