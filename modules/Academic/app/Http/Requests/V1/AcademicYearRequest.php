<?php

namespace Modules\Academic\App\Http\Requests\V1;


use Illuminate\Foundation\Http\FormRequest;

class AcademicYearRequest extends FormRequest
{
    public function authorize()
    {
        return true; // optionally add policy
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|min:1900|max:2100',
            'end_year' => 'required|integer|min:1900|max:2100|gte:start_year',
            'is_active' => 'sometimes|boolean'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'channel_id' => auth()->user()->channel_id
        ]);
    }
}
