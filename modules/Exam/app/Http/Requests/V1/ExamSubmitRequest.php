<?php

namespace Modules\Exam\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ExamSubmitRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'student_id'                     => 'required|integer|exists:students,id',
            'answers'                        => 'required|array',
            'answers.*.question_id'          => 'required|integer|exists:exam_questions,id',
            'answers.*.selected_option_id'   => 'nullable|integer|exists:exam_options,id',
            'answers.*.answer_text'          => 'nullable|string',
        ];
    }
}
