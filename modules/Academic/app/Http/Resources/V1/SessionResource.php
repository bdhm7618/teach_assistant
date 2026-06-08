<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'group_id'         => $this->group_id,
            'session_time_id'  => $this->session_time_id,
            'scheduled_at'     => $this->scheduled_at?->toDateTimeString(),
            'local_time'       => $this->local_time,
            'duration_minutes' => $this->duration_minutes,
            'type'             => $this->type,
            'status'           => $this->status,
            'location'         => $this->location,
            'notes'            => $this->notes,
        ];
    }
}
