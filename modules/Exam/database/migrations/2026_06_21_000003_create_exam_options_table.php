<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores answer choices for MCQ and true_false questions
        Schema::create('exam_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('exam_questions')->cascadeOnDelete();

            $table->string('text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('order')->default(0);

            $table->timestamps();

            $table->index(['question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_options');
    }
};
