<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => [
                'id' => $this->student->id ?? null,
                'name' => $this->student->name ?? null,
                'code' => $this->student->code ?? null,
            ],
            'group_id' => $this->group_id,
            'group' => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'code' => $this->group->code,
            ] : null,
            'enrollment_type' => $this->enrollment_type,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'agreed_monthly_fee' => $this->agreed_monthly_fee ? (float) $this->agreed_monthly_fee : null,
            'agreed_course_fee' => $this->agreed_course_fee ? (float) $this->agreed_course_fee : null,
            'agreed_session_fee' => $this->agreed_session_fee ? (float) $this->agreed_session_fee : null,
            'sessions_per_month' => $this->sessions_per_month,
            'used_sessions_count' => $this->used_sessions_count,
            'remaining_sessions_count' => $this->remaining_sessions_count,
            'notes' => $this->notes,
            'is_active' => $this->isActive(),
            'has_remaining_sessions' => $this->hasRemainingSessions(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

