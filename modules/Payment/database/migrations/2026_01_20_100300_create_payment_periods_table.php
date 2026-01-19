<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period_type', ['monthly', 'weekly', 'daily', 'session', 'custom'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->boolean('is_open')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['channel_id', 'period_type', 'year', 'month']);
            $table->index(['start_date', 'end_date']);
            $table->index(['is_open', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_periods');
    }
};

