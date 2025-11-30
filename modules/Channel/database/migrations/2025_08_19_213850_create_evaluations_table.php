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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('title'); 
            $table->enum('type', ['exam', 'quiz', 'assignment', 'project', 'participation', 'other'])->default('other');
            $table->decimal('score', 5, 2)->nullable(); 
            $table->decimal('max_score', 5, 2)->nullable();
            $table->string('grade')->nullable(); // e.g. A, B, C, or Pass/Fail
            $table->text('feedback')->nullable(); // teacher comments
            $table->date('evaluation_date')->nullable(); // when evaluation was done
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
