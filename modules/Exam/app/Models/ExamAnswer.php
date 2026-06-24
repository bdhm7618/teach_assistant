<?php

namespace Modules\Exam\App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    protected $table = 'exam_answers';

    protected $fillable = [
        'submission_id',
        'question_id',
        'selected_option_id',
        'answer_text',
        'marks_obtained',
        'is_correct',
    ];

    protected $casts = [
        'marks_obtained' => 'float',
        'is_correct'     => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(ExamSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(ExamOption::class, 'selected_option_id');
    }
}
