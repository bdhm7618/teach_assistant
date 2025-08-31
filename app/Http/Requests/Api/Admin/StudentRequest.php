<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends FormRequest
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
        $id = $this->route('student');

        return [
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255|unique:students,email,' . $id,
            'phone'    => 'required|string|max:20|unique:students,phone,' . $id,
            "group_id" => "required|exists:groups,id",
            'geneder'  => 'required|in:male,female',
            'password' => $this->isMethod('post')
                ? 'nullable|string|min:6'
                : 'nullable|string|min:6',
            'status'   => 'nullable|boolean',
            'image'    => 'nullable|string|max:500',
        ];
    }
}
