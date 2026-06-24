<?php

namespace Modules\Exam\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ExamRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'group_id'         => 'required|integer|exists:groups,id',
            'course_id'        => 'nullable|integer|exists:courses,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:5|max:600',
            'total_marks'      => 'sometimes|numeric|min:1',
            'pass_marks'       => 'sometimes|numeric|min:0',
            'allow_retake'     => 'sometimes|boolean',
            'max_attempts'     => 'sometimes|integer|min:1|max:10',
            'status'           => 'sometimes|in:draft,published,closed',
            'starts_at'        => 'nullable|date',
            'ends_at'          => 'nullable|date|after_or_equal:starts_at',
        ];
    }
}
