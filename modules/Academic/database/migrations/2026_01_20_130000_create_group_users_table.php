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
        Schema::create('group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // نوع الدور في المجموعة
            $table->enum('role_type', ['teacher', 'assistant', 'helper', 'coordinator'])->default('teacher');
            
            // حالة العضوية
            $table->enum('status', ['active', 'inactive', 'removed'])->default('active');
            
            // تاريخ الانضمام
            $table->timestamp('joined_at')->nullable();
            
            // ملاحظات
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['group_id', 'user_id']); // مستخدم واحد في مجموعة واحدة مرة واحدة
            $table->index(['group_id', 'role_type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['channel_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_users');
    }
};

