<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();

            $table->text('question');
            $table->enum('type', ['mcq', 'true_false', 'short_answer', 'essay']);
            $table->unsignedDecimal('marks', 6, 2)->default(1);
            $table->unsignedSmallInteger('order')->default(0);
            $table->text('explanation')->nullable(); // shown after grading

            $table->timestamps();

            $table->index(['exam_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
