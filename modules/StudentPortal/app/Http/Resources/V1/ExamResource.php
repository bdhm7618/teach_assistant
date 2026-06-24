<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'total_marks'      => $this->total_marks,
            'pass_marks'       => $this->pass_marks,
            'allow_retake'     => $this->allow_retake,
            'max_attempts'     => $this->max_attempts,
            'status'           => $this->status,
            'starts_at'        => $this->starts_at,
            'ends_at'          => $this->ends_at,
            'group'            => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
            ]),
            'my_attempts_count'  => $this->when(isset($this->my_attempts_count), $this->my_attempts_count),
            'my_latest_attempt'  => $this->when(isset($this->my_latest_attempt), $this->my_latest_attempt),
            'can_attempt'        => $this->when(isset($this->can_attempt), $this->can_attempt),
            'cannot_attempt_reason' => $this->when(isset($this->cannot_attempt_reason), $this->cannot_attempt_reason),
        ];
    }
}
