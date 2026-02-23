<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'channel_id',
        'student_id',
        'group_id',
        'enrollment_type',
        'status',
        'start_date',
        'end_date',
        'agreed_monthly_fee',
        'agreed_course_fee',
        'agreed_session_fee',
        'sessions_per_month',
        'used_sessions_count',
        'remaining_sessions_count',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'agreed_monthly_fee' => 'decimal:2',
        'agreed_course_fee' => 'decimal:2',
        'agreed_session_fee' => 'decimal:2',
        'sessions_per_month' => 'integer',
        'used_sessions_count' => 'integer',
        'remaining_sessions_count' => 'integer',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    /**
     * Get the group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if enrollment has remaining sessions
     */
    public function hasRemainingSessions(): bool
    {
        if ($this->remaining_sessions_count === null) {
            return true; // Unlimited or not set
        }
        return $this->remaining_sessions_count > 0;
    }

    /**
     * Calculate remaining sessions
     */
    public function calculateRemainingSessions(): int
    {
        if ($this->sessions_per_month === null) {
            return -1; // Unlimited
        }
        return max(0, $this->sessions_per_month - $this->used_sessions_count);
    }

    /**
     * Update remaining sessions count
     */
    public function updateRemainingSessions(): void
    {
        $this->remaining_sessions_count = $this->calculateRemainingSessions();
        $this->save();
    }

    /**
     * Increment used sessions count
     */
    public function incrementUsedSessions(int $count = 1): void
    {
        $this->increment('used_sessions_count', $count);
        $this->updateRemainingSessions();
    }
}

