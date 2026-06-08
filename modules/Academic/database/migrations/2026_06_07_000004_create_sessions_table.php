<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('session_time_id')->nullable()
                  ->constrained('session_times')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->enum('type', ['online', 'offline'])->default('offline');
            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status', 'scheduled_at']);
            $table->index(['channel_id', 'scheduled_at']);
            $table->index('session_time_id');
            $table->unique(['group_id', 'session_time_id', 'scheduled_at'], 'unique_session_time_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_sessions');
    }
};
