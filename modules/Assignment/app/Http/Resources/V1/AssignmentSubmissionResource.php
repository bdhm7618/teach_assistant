<?php

namespace Modules\Assignment\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'assignment_id'    => $this->assignment_id,
            'student_id'       => $this->student_id,
            'answer_text'      => $this->answer_text,
            'is_late'          => $this->is_late,
            'marks_obtained'   => $this->marks_obtained,
            'is_pass'          => $this->is_pass,
            'status'           => $this->status,
            'teacher_feedback' => $this->teacher_feedback,
            'submitted_at'     => $this->submitted_at->toIso8601String(),
            'attachments'      => AssignmentAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),
            'student'          => $this->whenLoaded('student', fn() => [
                'id'   => $this->student->id,
                'name' => $this->student->name,
                'code' => $this->student->code,
            ]),
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
