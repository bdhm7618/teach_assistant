<?php

namespace Modules\Attendance\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Modules\Attendance\App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'student_id',
        'group_id',
        'session_time_id',
        'date',
        'status',
        'notes',
        'channel_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get status attribute as enum
     */
    public function getStatusAttribute($value): AttendanceStatus
    {
        return AttendanceStatus::from($value);
    }

    /**
     * Set status attribute
     */
    public function setStatusAttribute($value): void
    {
        if ($value instanceof AttendanceStatus) {
            $this->attributes['status'] = $value->value;
        } else {
            $this->attributes['status'] = $value;
        }
    }

    /**
     * Get the student that this attendance belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    /**
     * Get the group that this attendance belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Group::class);
    }

    /**
     * Get the session time that this attendance belongs to
     */
    public function sessionTime(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academic\App\Models\SessionTime::class);
    }

    /**
     * Get the channel that owns this attendance
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, AttendanceStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope for filtering by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for filtering by group
     */
    public function scopeByGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Check if attendance is positive (present or excused)
     */
    public function isPositive(): bool
    {
        return $this->status->isPositive();
    }
}

