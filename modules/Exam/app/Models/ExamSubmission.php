<?php

namespace Modules\Exam\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Channel\App\Traits\HasChannelScope;

class ExamSubmission extends Model
{
    use HasChannelScope;

    protected $table = 'exam_submissions';

    protected $fillable = [
        'channel_id',
        'exam_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'total_marks',
        'obtained_marks',
        'is_pass',
        'status',
        'teacher_notes',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'submitted_at'  => 'datetime',
        'total_marks'   => 'float',
        'obtained_marks'=> 'float',
        'is_pass'       => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class, 'submission_id');
    }

    public function autoGrade(): void
    {
        $exam = $this->exam()->with('questions.correctOption')->first();
        $obtainedMarks = 0;

        foreach ($this->answers as $answer) {
            $question = $exam->questions->firstWhere('id', $answer->question_id);
            if (!$question) continue;

            if ($question->isObjective()) {
                $isCorrect = $question->correctOption
                    && $answer->selected_option_id === $question->correctOption->id;
                $marksObtained = $isCorrect ? $question->marks : 0;

                $answer->update([
                    'is_correct'      => $isCorrect,
                    'marks_obtained'  => $marksObtained,
                ]);

                $obtainedMarks += $marksObtained;
            } else {
                // Essay / short_answer — marks from teacher_grade; skip auto-grade
                $obtainedMarks += (float) ($answer->marks_obtained ?? 0);
            }
        }

        $this->update([
            'obtained_marks' => $obtainedMarks,
            'total_marks'    => $exam->total_marks,
            'is_pass'        => $obtainedMarks >= $exam->pass_marks,
            'status'         => $this->hasUngradedEssays() ? 'submitted' : 'graded',
        ]);
    }

    private function hasUngradedEssays(): bool
    {
        return $this->answers()
            ->whereHas('question', fn($q) => $q->whereIn('type', ['short_answer', 'essay']))
            ->whereNull('marks_obtained')
            ->exists();
    }
}
