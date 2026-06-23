<?php

namespace Modules\Exam\App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    protected $table = 'exam_questions';

    protected $fillable = [
        'channel_id',
        'exam_id',
        'question',
        'type',
        'marks',
        'order',
        'explanation',
    ];

    protected $casts = [
        'marks' => 'float',
        'order' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function options()
    {
        return $this->hasMany(ExamOption::class, 'question_id')->orderBy('order');
    }

    public function correctOption()
    {
        return $this->hasOne(ExamOption::class, 'question_id')->where('is_correct', true);
    }

    public function isObjective(): bool
    {
        return in_array($this->type, ['mcq', 'true_false']);
    }
}
