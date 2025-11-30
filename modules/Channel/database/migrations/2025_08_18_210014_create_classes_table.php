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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->year("start_year")->nullable();
            $table->year("end_year")->nullable();
            $table->string("name", 255);
            $table->string("code")->unique()->nullable();
            $table->tinyInteger('status')->default(1);
            $table->foreignId("channel_id")->constrained("channels")->restrictOnDelete();
            $table->foreignId("subject_id")->constrained("subjects")->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
