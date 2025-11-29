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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string("name", 255);
            $table->string("code")->unique()->nullable();
            $table->foreignId('class_id')->constrained('classes')->nullOnDelete();
            $table->tinyInteger("numbre_of_sessions")->default(8);
            $table->decimal("price_of_group")->nullable();
            $table->tinyInteger('status')->default(1);
            $table->foreignId("channel_id")->constrained("channels")->restrictOnDelete();
            $table->foreignId("teacher_id")->constrained("teachers")->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
