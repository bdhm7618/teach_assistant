<?php

namespace Modules\Exam\App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamOption extends Model
{
    protected $table = 'exam_options';

    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
        'order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'order'      => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'question_id');
    }
}
