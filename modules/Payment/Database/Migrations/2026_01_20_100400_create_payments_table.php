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
            $table->foreignId('payment_period_id')->nullable()->constrained('payment_periods')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('installments')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->dateTime('payment_date');
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'vodafone_cash',
                'orange_money',
                'etisalat_cash',
                'easy_pay',
                'credit_card',
                'debit_card',
                'online',
                'other'
            ])->default('cash');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'payment_date']);
            $table->index(['group_id', 'payment_date']);
            $table->index(['payment_period_id', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->index(['payment_date', 'status']);
            $table->index('reference_number');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
