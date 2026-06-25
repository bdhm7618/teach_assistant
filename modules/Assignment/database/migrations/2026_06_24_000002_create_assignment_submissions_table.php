<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_late')->default(false);
            $table->unsignedDecimal('marks_obtained', 8, 2)->nullable();
            $table->boolean('is_pass')->nullable();
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->text('teacher_feedback')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['channel_id', 'assignment_id', 'status']);
            $table->index(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
