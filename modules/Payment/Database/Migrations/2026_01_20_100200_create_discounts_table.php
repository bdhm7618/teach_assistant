<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['all', 'groups', 'students'])->default('all');
            $table->foreignId('channel_id')->nullable()->constrained('channels')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index(['channel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};

