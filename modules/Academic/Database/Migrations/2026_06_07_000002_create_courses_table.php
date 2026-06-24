<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['online', 'offline', 'hybrid'])->default('offline');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['channel_id', 'status']);
            $table->index(['channel_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
