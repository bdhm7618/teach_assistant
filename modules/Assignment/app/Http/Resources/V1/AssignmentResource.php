<?php

namespace Modules\Assignment\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'group_id'              => $this->group_id,
            'course_id'             => $this->course_id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'instructions'          => $this->instructions,
            'total_marks'           => $this->total_marks,
            'pass_marks'            => $this->pass_marks,
            'status'                => $this->status,
            'due_at'                => $this->due_at?->toIso8601String(),
            'is_past_due'           => $this->isPastDue(),
            'allow_late_submission' => $this->allow_late_submission,
            'late_penalty_percent'  => $this->late_penalty_percent,
            'submissions_count'     => $this->whenCounted('submissions'),
            'attachments'           => AssignmentAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),
            'created_at'            => $this->created_at->toIso8601String(),
            'updated_at'            => $this->updated_at->toIso8601String(),
        ];
    }
}
