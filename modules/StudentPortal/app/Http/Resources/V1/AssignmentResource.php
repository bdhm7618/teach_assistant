<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

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
            'is_past_due'           => $this->isPastDue(),
            'group'                 => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
            ]),
            'attachments' => $this->whenLoaded('attachments', fn() =>
                $this->attachments->map(fn($a) => [
                    'id'        => $a->id,
                    'file_name' => $a->file_name,
                    'file_url'  => asset('storage/' . $a->file_path),
                    'file_size' => $a->file_size,
                ])
            ),
            'my_submission'      => $this->when(isset($this->my_submission), $this->my_submission),
            'can_submit'         => $this->when(isset($this->can_submit), $this->can_submit),
            'cannot_submit_reason' => $this->when(isset($this->cannot_submit_reason), $this->cannot_submit_reason),
        ];
    }
}
