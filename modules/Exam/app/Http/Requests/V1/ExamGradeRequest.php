<?php

namespace Modules\Exam\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ExamGradeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'grades'                       => 'required|array',
            'grades.*.answer_id'           => 'required|integer|exists:exam_answers,id',
            'grades.*.marks_obtained'      => 'required|numeric|min:0',
            'teacher_notes'                => 'nullable|string|max:1000',
        ];
    }
}
