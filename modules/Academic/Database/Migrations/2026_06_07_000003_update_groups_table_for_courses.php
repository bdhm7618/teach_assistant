<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('id')
                  ->constrained('courses')->cascadeOnDelete();

            $table->enum('payment_model', ['per_course', 'monthly', 'per_session'])
                  ->default('monthly')->after('price');

            $table->date('starts_at')->nullable()->after('payment_model');
            $table->date('ends_at')->nullable()->after('starts_at');

            $table->enum('status', ['active', 'full', 'archived'])->default('active')->after('is_active');

            $table->softDeletes();

            $table->index(['course_id', 'status']);
        });

        // Make class_grade_id nullable (keep as metadata, not required)
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('class_grade_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['course_id', 'status']);
            $table->dropForeign(['course_id']);
            $table->dropColumn(['course_id', 'payment_model', 'starts_at', 'ends_at', 'status']);
            $table->foreignId('class_grade_id')->nullable(false)->change();
        });
    }
};
