<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'name',
        'code',
        'level_number',
        'stage',
        'description',
        'is_active',
        'is_default',
        'channel_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get class grades using this level
     */
    public function classGrades()
    {
        return $this->hasMany(ClassGrade::class);
    }

    /**
     * Scope to get only default system levels
     */
    public function scopeDefault($query)
    {
        return $query->whereNull('channel_id')->where('is_default', true);
    }

    /**
     * Scope to get channel-specific levels
     */
    public function scopeChannelSpecific($query, $channelId = null)
    {
        $channelId = $channelId ?? auth('user')->user()?->channel_id;
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope to get available levels for a channel (default + channel-specific)
     */
    public function scopeAvailableForChannel($query, $channelId = null)
    {
        $channelId = $channelId ?? auth('user')->user()?->channel_id;
        return $query->where(function ($q) use ($channelId) {
            $q->whereNull('channel_id') // System defaults
                ->orWhere('channel_id', $channelId); // Channel-specific
        })->where('is_active', true);
    }
}
