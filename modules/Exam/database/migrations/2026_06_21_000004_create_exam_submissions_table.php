<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->unsignedTinyInteger('attempt_number')->default(1);

            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();

            $table->unsignedDecimal('total_marks', 8, 2)->nullable();   // exam total_marks at time of submission
            $table->unsignedDecimal('obtained_marks', 8, 2)->nullable();
            $table->boolean('is_pass')->nullable();

            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');

            $table->text('teacher_notes')->nullable();

            $table->timestamps();

            // One active (in_progress) submission per student per exam
            $table->index(['exam_id', 'student_id', 'attempt_number']);
            $table->index(['channel_id', 'exam_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_submissions');
    }
};
