<?php

namespace Modules\ParentPortal\App\Http\Resources\V1;

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
            'status'           => $this->status,
            'starts_at'        => $this->starts_at,
            'ends_at'          => $this->ends_at,
            'group'            => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
            ]),
            // Read-only: the child's most recent attempt/result, set by the controller.
            'my_latest_attempt' => $this->when(isset($this->my_latest_attempt), $this->my_latest_attempt),
        ];
    }
}
