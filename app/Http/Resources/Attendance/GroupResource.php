<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_time_id'      => $this->id,
            'group_id'           => $this->group->id,
            'group_name'         => $this->group->name,
            'group_code'         => $this->group->code,
            'class_id'           => $this->group->class_id,
            'numbre_of_sessions' => $this->group->numbre_of_sessions,
            'price_of_group'     => $this->group->price_of_group,
            'group_status'       => $this->group->status,
            'channel_id'         => $this->group->channel_id,
            'teacher_id'         => $this->group->teacher_id,
            'session_time'         => $this->session_time,
            'day_name'           => $this->day_name,
            'session_time_status'  => $this->status,

            'students' => $this->whenLoaded('group', function () {
                return $this->group->students->map(function ($student) {
                    $attendance = $student->attendanceForToday
                        ->firstWhere('session_time_id', $this->id);

                    return [
                        'id'              => $student->id,
                        'name'            => $student->name,
                        'email'           => $student->email,
                        'group_id'        => $student->group_id,
                        'phone'           => $student->phone,
                        'code'            => $student->code,
                        
                        'attendance_today' => $attendance,
                    ];
                });
            }),

        ];
    }
}
