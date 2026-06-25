<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Channel\App\Models\Channel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active')->after('slug');
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->string('type', 20)->default('teacher')->after('trial_ends_at');
        });

        // Backfill slugs for existing channels
        Channel::withoutGlobalScopes()->get()->each(function ($channel) {
            $base = Str::slug($channel->name ?: 'channel');
            $slug = $base;
            $i    = 1;
            while (Channel::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $channel->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $channel->updateQuietly(['slug' => $slug]);
        });

        // Now make slug non-nullable
        Schema::table('channels', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['slug', 'status', 'trial_ends_at', 'type']);
        });
    }
};
