<?php

namespace Modules\Assignment\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marks_obtained'   => ['required', 'numeric', 'min:0'],
            'teacher_feedback' => ['nullable', 'string'],
        ];
    }
}
