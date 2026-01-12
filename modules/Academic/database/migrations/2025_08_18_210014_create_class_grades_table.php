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
        Schema::create('class_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('grade_level'); // 1 → 12
            $table->enum('stage', ['primary', 'preparatory', 'secondary']);
            $table->boolean('is_active')->default(true);
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['channel_id', 'grade_level', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
