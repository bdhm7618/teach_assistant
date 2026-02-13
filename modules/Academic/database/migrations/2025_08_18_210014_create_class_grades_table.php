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
            $table->string('name')->nullable();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['channel_id', 'level_id'], 'class_grades_channel_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_grades');
    }
};
