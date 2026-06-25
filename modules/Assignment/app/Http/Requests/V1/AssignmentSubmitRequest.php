<?php

namespace Modules\Assignment\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'    => ['required', 'integer', 'exists:students,id'],
            'answer_text'   => ['nullable', 'string'],
            'attachments'   => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasText  = !empty($this->input('answer_text'));
            $hasFiles = $this->hasFile('attachments');

            if (!$hasText && !$hasFiles) {
                $validator->errors()->add('answer_text', __('assignment::app.submission.answer_required'));
            }
        });
    }
}
