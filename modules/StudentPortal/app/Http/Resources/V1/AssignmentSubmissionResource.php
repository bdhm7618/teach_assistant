<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'assignment_id'    => $this->assignment_id,
            'status'           => $this->status,
            'answer_text'      => $this->answer_text,
            'is_late'          => $this->is_late,
            'marks_obtained'   => $this->marks_obtained,
            'is_pass'          => $this->is_pass,
            'teacher_feedback' => $this->teacher_feedback,
            'submitted_at'     => $this->submitted_at,
            'assignment'       => $this->whenLoaded('assignment', fn() => [
                'id'          => $this->assignment->id,
                'title'       => $this->assignment->title,
                'total_marks' => $this->assignment->total_marks,
                'pass_marks'  => $this->assignment->pass_marks,
            ]),
            'attachments' => $this->whenLoaded('attachments', fn() =>
                $this->attachments->map(fn($a) => [
                    'id'        => $a->id,
                    'file_name' => $a->file_name,
                    'file_url'  => asset('storage/' . $a->file_path),
                    'file_size' => $a->file_size,
                ])
            ),
        ];
    }
}
