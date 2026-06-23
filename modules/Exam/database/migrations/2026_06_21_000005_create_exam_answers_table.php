<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('exam_submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();

            // For MCQ / true_false — the chosen option
            $table->foreignId('selected_option_id')->nullable()->constrained('exam_options')->nullOnDelete();
            // For short_answer / essay — free text
            $table->text('answer_text')->nullable();

            $table->unsignedDecimal('marks_obtained', 6, 2)->nullable();
            $table->boolean('is_correct')->nullable(); // null = not yet graded (essays)

            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
            $table->index(['submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
