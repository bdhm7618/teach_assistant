<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('assignment_submissions')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('type', ['assignment', 'submission']);
            $table->timestamps();

            $table->index(['assignment_id']);
            $table->index(['submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_attachments');
    }
};
