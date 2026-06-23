<?php

namespace Modules\Exam\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Channel\App\Traits\HasChannelScope;

class Exam extends Model
{
    use HasChannelScope, SoftDeletes;

    protected $table = 'exams';

    protected $fillable = [
        'channel_id',
        'group_id',
        'course_id',
        'title',
        'description',
        'duration_minutes',
        'total_marks',
        'pass_marks',
        'allow_retake',
        'max_attempts',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'allow_retake'     => 'boolean',
        'max_attempts'     => 'integer',
        'total_marks'      => 'float',
        'pass_marks'       => 'float',
        'duration_minutes' => 'integer',
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Group::class);
    }

    public function course()
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Course::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order');
    }

    public function submissions()
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function canBeAttemptedBy(int $studentId): array
    {
        if (!$this->isPublished()) {
            return [false, 'exam.not_published'];
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return [false, 'exam.not_started'];
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return [false, 'exam.ended'];
        }

        $attemptCount = $this->submissions()
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($attemptCount >= $this->max_attempts) {
            return [false, 'exam.max_attempts_reached'];
        }

        $inProgress = $this->submissions()
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->exists();

        if ($inProgress) {
            return [false, 'exam.already_in_progress'];
        }

        return [true, null];
    }
}
