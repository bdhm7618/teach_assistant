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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            
            // نوع الاشتراك
            $table->enum('enrollment_type', ['monthly', 'course', 'session_package'])->default('monthly');
            
            // حالة الاشتراك
            $table->enum('status', ['active', 'paused', 'canceled', 'completed'])->default('active');
            
            // تواريخ الاشتراك
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // التسعير المتفق عليه
            $table->decimal('agreed_monthly_fee', 10, 2)->nullable(); // للاشتراك الشهري
            $table->decimal('agreed_course_fee', 10, 2)->nullable(); // للكورس الكامل
            $table->decimal('agreed_session_fee', 10, 2)->nullable(); // للحصة الواحدة
            
            // الباقة الشهرية - عدد الحصص
            $table->integer('sessions_per_month')->nullable()->default(8); // عدد الحصص في الشهر (مثلاً: 8 حصص)
            $table->integer('used_sessions_count')->default(0); // عدد الحصص المستخدمة
            $table->integer('remaining_sessions_count')->nullable(); // عدد الحصص المتبقية (يتم حسابه تلقائياً)
            
            // ملاحظات
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['student_id', 'group_id', 'status']);
            $table->index(['group_id', 'status']);
            $table->index(['channel_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};

