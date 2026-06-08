<?php

namespace Modules\Channel\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel_name' => 'required|string|max:255',
            'channel_type' => 'required|in:teacher,center',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'phone'        => ['required', 'string', 'unique:users,phone', 'regex:/^01[0125][0-9]{8}$/'],
            'gender'       => 'required|in:male,female',
            'password'     => 'required|string|min:6|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
        ];
    }
}
