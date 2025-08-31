<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GroupRequest extends FormRequest
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
        $days = implode(",", array_keys(config("days")));

        return [
            'name'               => 'required|string|max:255',
            'class_id'           => 'required|integer|exists:classes,id',
            'numbre_of_sessions' => 'required|integer|min:1',
            'price_of_group'     => 'required|numeric|min:0',
            'status'             => 'boolean',
            'teacher_id'         => 'nullable|integer|exists:teachers,id',
            "times" => ["required", "array", "min:2"],
            "times.*.class_time" => ["required", "date_format:H:i"],
            "times.*.day_name" => ["required", "in:$days"]
        ];
    }
}
