<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Student\App\Models\Student;
use Modules\Academic\App\Models\Group;

class StudentEnrollmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $enrollmentId = $this->route('student-enrollment') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'student_id' => [
                'required',
                'integer',
                $this->belongsToChannel(Student::class),
            ],
            'group_id' => [
                'required',
                'integer',
                $this->belongsToChannel(Group::class),
            ],
            'enrollment_type' => 'required|in:monthly,course,session_package',
            'status' => 'nullable|in:active,paused,canceled,completed',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'agreed_monthly_fee' => 'nullable|numeric|min:0',
            'agreed_course_fee' => 'nullable|numeric|min:0',
            'agreed_session_fee' => 'nullable|numeric|min:0',
            'sessions_per_month' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}

