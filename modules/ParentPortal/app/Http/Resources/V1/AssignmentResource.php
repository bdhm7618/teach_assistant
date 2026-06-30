<?php

namespace Modules\ParentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'instructions'          => $this->instructions,
            'total_marks'           => $this->total_marks,
            'pass_marks'            => $this->pass_marks,
            'status'                => $this->status,
            'due_at'                => $this->due_at,
            'allow_late_submission' => $this->allow_late_submission,
            'late_penalty_percent'  => $this->late_penalty_percent,
            'group'                 => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
            ]),
            // Read-only: the child's submission/result, set by the controller.
            'my_submission' => $this->when(isset($this->my_submission), $this->my_submission),
        ];
    }
}
