<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => $this->id,
            'status'                   => $this->status,
            'enrollment_type'          => $this->enrollment_type,
            'start_date'               => $this->start_date?->toDateString(),
            'end_date'                 => $this->end_date?->toDateString(),
            'agreed_monthly_fee'       => $this->agreed_monthly_fee,
            'agreed_course_fee'        => $this->agreed_course_fee,
            'agreed_session_fee'       => $this->agreed_session_fee,
            'sessions_per_month'       => $this->sessions_per_month,
            'used_sessions_count'      => $this->used_sessions_count,
            'remaining_sessions_count' => $this->remaining_sessions_count,
            'notes'                    => $this->notes,
            'group'                    => $this->whenLoaded('group', fn() => [
                'id'     => $this->group->id,
                'name'   => $this->group->name,
                'code'   => $this->group->code,
                'status' => $this->group->status,
            ]),
        ];
    }
}
