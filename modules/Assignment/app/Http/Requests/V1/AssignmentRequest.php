<?php

namespace Modules\Assignment\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class AssignmentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'group_id'              => ['required', 'integer', $this->belongsToChannel('groups')],
            'course_id'             => ['nullable', 'integer', $this->belongsToChannel('courses')],
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'instructions'          => ['nullable', 'string'],
            'total_marks'           => ['sometimes', 'numeric', 'min:1'],
            'pass_marks'            => ['sometimes', 'numeric', 'min:0', 'lte:total_marks'],
            'status'                => ['sometimes', 'in:draft,published,closed'],
            'due_at'                => ['nullable', 'date', 'after:now'],
            'allow_late_submission' => ['sometimes', 'boolean'],
            'late_penalty_percent'  => ['sometimes', 'integer', 'min:0', 'max:100'],
            'attachments'           => ['sometimes', 'array'],
            'attachments.*'         => ['file', 'max:10240'],
        ];
    }
}
