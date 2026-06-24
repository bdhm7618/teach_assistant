<?php

namespace Modules\Assignment\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Channel\App\Traits\HasChannelScope;

class Assignment extends Model
{
    use HasChannelScope, SoftDeletes;

    protected $table = 'assignments';

    protected $fillable = [
        'channel_id',
        'group_id',
        'course_id',
        'title',
        'description',
        'instructions',
        'total_marks',
        'pass_marks',
        'status',
        'due_at',
        'allow_late_submission',
        'late_penalty_percent',
    ];

    protected $casts = [
        'total_marks'           => 'float',
        'pass_marks'            => 'float',
        'due_at'                => 'datetime',
        'allow_late_submission' => 'boolean',
        'late_penalty_percent'  => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Group::class);
    }

    public function course()
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Course::class);
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function attachments()
    {
        return $this->hasMany(AssignmentAttachment::class)->where('type', 'assignment');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isPastDue(): bool
    {
        return $this->due_at && now()->gt($this->due_at);
    }

    public function canBeSubmittedBy(int $studentId): array
    {
        if (!$this->isPublished()) {
            return [false, 'assignment.not_published'];
        }

        if ($this->isClosed()) {
            return [false, 'assignment.closed'];
        }

        $alreadySubmitted = $this->submissions()
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadySubmitted) {
            return [false, 'assignment.already_submitted'];
        }

        if ($this->isPastDue() && !$this->allow_late_submission) {
            return [false, 'assignment.past_due'];
        }

        return [true, null];
    }
}
