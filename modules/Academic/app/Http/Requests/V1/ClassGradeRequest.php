<?php

namespace Modules\Academic\App\Http\Requests\V1;


use Illuminate\Foundation\Http\FormRequest;

class ClassGradeRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add policies if needed
    }

    public function rules()
    {
        return [
            'grade_level' => 'required|integer|min:1|max:12',
            'stage' => 'required|in:primary,preparatory,secondary',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_active' => 'sometimes|boolean'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'channel_id' => auth("user")->user()->channel_id,
        ]);
    }
}
