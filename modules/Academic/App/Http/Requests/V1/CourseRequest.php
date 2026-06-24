<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:online,offline,hybrid',
            'status'      => 'sometimes|in:draft,active,archived',
            'subject_id'  => 'nullable|integer|exists:subjects,id',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
        ];
    }
}
