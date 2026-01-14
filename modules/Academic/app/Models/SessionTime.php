<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class SessionTime extends Model
{
    use HasChannelScope;
    
    protected $fillable = [
        'day',
        'start_time',
        'end_time',
        'group_id',
        'is_active',
        'channel_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the group that owns the session time
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Check if this session time conflicts with another session time
     */
    public function conflictsWith(SessionTime $other): bool
    {
        // Same day and overlapping times
        if ($this->day !== $other->day) {
            return false;
        }

        // Check time overlap
        return $this->timeOverlaps($other->start_time, $other->end_time);
    }

    /**
     * Check if time overlaps with given start and end times
     */
    protected function timeOverlaps($otherStart, $otherEnd): bool
    {
        $thisStart = strtotime($this->start_time);
        $thisEnd = strtotime($this->end_time);
        $otherStartTime = strtotime($otherStart);
        $otherEndTime = strtotime($otherEnd);

        // Check if times overlap
        return ($thisStart < $otherEndTime && $thisEnd > $otherStartTime);
    }
}
