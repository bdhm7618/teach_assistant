<?php

namespace App\Http\Requests\Api\Admin;


use Illuminate\Foundation\Http\FormRequest;

class TeacherRequest extends FormRequest
{
    public function rules()
    {
       
        return [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:teachers,email,' . $this->teacher->id,
            'phone'     => 'required|string|max:20|unique:teachers,phone,' . $this->teacher->id,
            'gender'    => 'required|in:male,female',
            'password'  => $this->isMethod('post') ? 'required|min:6' : 'nullable|min:6',
            'status'    => 'boolean',
            'image'     => 'nullable|string|max:500',
            'channel_id' => 'required|exists:channels,id',
        ];
    }
}
