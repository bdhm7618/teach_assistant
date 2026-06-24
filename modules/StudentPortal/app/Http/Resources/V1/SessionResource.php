<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'scheduled_at'     => $this->scheduled_at,
            'local_time'       => $this->local_time,
            'duration_minutes' => $this->duration_minutes,
            'type'             => $this->type,
            'status'           => $this->status,
            'location'         => $this->location,
            'notes'            => $this->notes,
            'group'            => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
                'code' => $this->group->code,
            ]),
            'my_attendance' => $this->when(
                isset($this->my_attendance_status),
                fn() => $this->my_attendance_status
            ),
        ];
    }
}
