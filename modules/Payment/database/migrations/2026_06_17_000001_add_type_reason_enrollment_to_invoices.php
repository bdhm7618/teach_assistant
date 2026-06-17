<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Differentiate invoice purpose — ad_hoc requires a reason
            $table->enum('type', ['monthly', 'session', 'enrollment_fee', 'ad_hoc'])
                ->default('monthly')
                ->after('notes');

            // Required for ad_hoc type; optional for others
            $table->string('reason', 500)->nullable()->after('type');

            // Link back to the enrollment that triggered this invoice (nullable for ad-hoc)
            $table->foreignId('enrollment_id')
                ->nullable()
                ->after('group_id')
                ->constrained('student_enrollments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropColumn(['type', 'reason', 'enrollment_id']);
        });
    }
};
