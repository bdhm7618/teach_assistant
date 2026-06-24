<?php

namespace Modules\Assignment\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Channel\App\Traits\HasChannelScope;

class AssignmentSubmission extends Model
{
    use HasChannelScope;

    protected $table = 'assignment_submissions';

    protected $fillable = [
        'channel_id',
        'assignment_id',
        'student_id',
        'answer_text',
        'is_late',
        'marks_obtained',
        'is_pass',
        'status',
        'teacher_feedback',
        'submitted_at',
    ];

    protected $casts = [
        'is_late'        => 'boolean',
        'is_pass'        => 'boolean',
        'marks_obtained' => 'float',
        'submitted_at'   => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    public function attachments()
    {
        return $this->hasMany(AssignmentAttachment::class, 'submission_id')->where('type', 'submission');
    }

    public function grade(float $marksObtained, ?string $feedback = null): void
    {
        $assignment = $this->assignment;

        $finalMarks = $marksObtained;

        // Apply late penalty if applicable
        if ($this->is_late && $assignment->late_penalty_percent > 0) {
            $penalty     = $marksObtained * ($assignment->late_penalty_percent / 100);
            $finalMarks  = max(0, $marksObtained - $penalty);
        }

        $this->update([
            'marks_obtained'   => $finalMarks,
            'is_pass'          => $finalMarks >= $assignment->pass_marks,
            'teacher_feedback' => $feedback,
            'status'           => 'graded',
        ]);
    }
}
