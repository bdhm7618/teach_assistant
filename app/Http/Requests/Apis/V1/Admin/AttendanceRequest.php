<?php

namespace App\Http\Requests\Api\Admin;

use App\Rules\StudentIdsRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'session_time_id'   => 'required|exists:session_times,id',
            "attendance"   => "required|array",
            "attendance.*.student_id" =>["required" , "exists:students,id" , new StudentIdsRule($this->input("session_time_id"))],
            "attendance.*.status"  =>  'required|in:present,absent,late',
        ];
    }
}
