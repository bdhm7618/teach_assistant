<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('password')->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            // Multi-tenant: email/phone unique per channel, not globally.
            $table->unique(['channel_id', 'phone']);
            $table->unique(['channel_id', 'email']);
            $table->index('channel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
