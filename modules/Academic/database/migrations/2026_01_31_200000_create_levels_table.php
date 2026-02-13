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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Level name (e.g., "3 Secondary", "Beginner", "Advanced")
            $table->string('code')->nullable(); // Optional code for reference
            $table->unsignedTinyInteger('level_number')->nullable(); // 1 → 12 (for academic levels)
            $table->enum('stage', ['primary', 'preparatory', 'secondary', 'college', 'custom'])->nullable(); // For academic levels
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // System default levels
            $table->foreignId('channel_id')->nullable()->constrained()->cascadeOnDelete(); // null = system default, not null = channel-specific
            $table->timestamps();

            // Unique constraint: name must be unique per channel (or system if channel_id is null)
            $table->unique(['channel_id', 'name'], 'levels_channel_name_unique');

            // Unique constraint for academic format (level_number + stage)
            $table->unique(['channel_id', 'level_number', 'stage'], 'levels_academic_unique');

            // Index for faster queries
            $table->index(['channel_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
