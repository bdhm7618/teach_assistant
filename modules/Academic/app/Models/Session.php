<?php

namespace Modules\Academic\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\SessionTime;
use Modules\Channel\App\Traits\HasChannelScope;

class Session extends Model
{
    use HasChannelScope;

    protected $table = 'group_sessions';

    protected $fillable = [
        'channel_id', 'group_id', 'session_time_id',
        'scheduled_at', 'duration_minutes', 'type', 'status', 'location', 'notes',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function sessionTime()
    {
        return $this->belongsTo(SessionTime::class);
    }

    public function getLocalTimeAttribute(): string
    {
        return $this->scheduled_at->setTimezone('Africa/Cairo')->toDateTimeString();
    }

    public function scopeScheduled($q) { return $q->where('status', 'scheduled'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeCancelled($q) { return $q->where('status', 'cancelled'); }
    public function scopeLive($q)      { return $q->where('status', 'live'); }

    public function canBeEdited(): bool
    {
        return $this->status === 'scheduled';
    }
}
