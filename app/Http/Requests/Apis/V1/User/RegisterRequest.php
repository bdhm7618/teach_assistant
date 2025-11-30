<?php

namespace App\Http\Requests\Apis\V1\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|string|min:3|max:191',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'required|string|min:9|max:20|unique:users,phone',
            'gender'        => 'required|in:male,female',
            'password'      => 'required|min:6|confirmed',
        ];
    }
}
