<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'online'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'payment_date']);
            $table->index(['group_id', 'payment_date']);
            $table->index(['payment_date', 'status']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
