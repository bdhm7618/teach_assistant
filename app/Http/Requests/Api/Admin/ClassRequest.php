<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'start_year' => 'required|integer|min:1900|max:2100',
            'end_year'   => 'required|integer|min:1900|max:2100|gte:start_year',
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:classes,code,' . $this?->class?->id,
            'status'     => 'boolean',
            'channel_id' => 'required|integer|exists:channels,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ];
    }
}
