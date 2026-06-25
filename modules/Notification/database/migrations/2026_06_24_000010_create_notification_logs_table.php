<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('notifiable_type');   // e.g. Modules\Student\App\Models\Student
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type');              // enrollment_confirmed, invoice_created, etc.
            $table->string('channel')->default('email'); // mail | sms (future)
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'notifiable_type', 'notifiable_id']);
            $table->index(['channel_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
