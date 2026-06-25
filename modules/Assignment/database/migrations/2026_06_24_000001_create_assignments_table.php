<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedDecimal('total_marks', 8, 2)->default(100);
            $table->unsignedDecimal('pass_marks', 8, 2)->default(50);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->dateTime('due_at')->nullable();
            $table->boolean('allow_late_submission')->default(false);
            $table->unsignedSmallInteger('late_penalty_percent')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['channel_id', 'group_id', 'status']);
            $table->index(['channel_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
