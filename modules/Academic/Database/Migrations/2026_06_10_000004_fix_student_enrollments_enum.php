<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any existing data to new enum values before altering column
        DB::table('student_enrollments')
            ->where('enrollment_type', 'course')
            ->update(['enrollment_type' => 'per_course']);

        DB::table('student_enrollments')
            ->where('enrollment_type', 'session_package')
            ->update(['enrollment_type' => 'per_session']);

        // Alter enum to match groups.payment_model values: monthly | per_course | per_session
        DB::statement("ALTER TABLE student_enrollments MODIFY COLUMN enrollment_type ENUM('monthly','per_course','per_session') NOT NULL DEFAULT 'monthly'");
    }

    public function down(): void
    {
        DB::table('student_enrollments')
            ->where('enrollment_type', 'per_course')
            ->update(['enrollment_type' => 'course']);

        DB::table('student_enrollments')
            ->where('enrollment_type', 'per_session')
            ->update(['enrollment_type' => 'session_package']);

        DB::statement("ALTER TABLE student_enrollments MODIFY COLUMN enrollment_type ENUM('monthly','course','session_package') NOT NULL DEFAULT 'monthly'");
    }
};
