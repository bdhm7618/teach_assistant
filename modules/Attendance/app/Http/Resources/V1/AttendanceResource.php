<?php

namespace Modules\Attendance\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'group' => [
                'id' => $this->group->id ?? null,
                'name' => $this->group->name ?? null,
                'code' => $this->group->code ?? null,
            ],
            'session_time_id' => $this->session_time_id,
            'session_time' => $this->sessionTime ? [
                'id' => $this->sessionTime->id,
                'start_time' => $this->sessionTime->start_time ?? null,
                'end_time' => $this->sessionTime->end_time ?? null,
            ] : null,
            'date' => $this->date?->format('Y-m-d H:i:s'),
            'date_formatted' => $this->date?->format('Y-m-d'),
            'time' => $this->date?->format('H:i:s'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_positive' => $this->isPositive(),
            'notes' => $this->notes,
            'channel_id' => $this->channel_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

