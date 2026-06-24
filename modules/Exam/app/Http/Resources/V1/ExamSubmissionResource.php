<?php

namespace Modules\Exam\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'exam_id'        => $this->exam_id,
            'student_id'     => $this->student_id,
            'attempt_number' => $this->attempt_number,
            'started_at'     => $this->started_at?->toISOString(),
            'submitted_at'   => $this->submitted_at?->toISOString(),
            'total_marks'    => $this->total_marks,
            'obtained_marks' => $this->obtained_marks,
            'percentage'     => $this->total_marks > 0
                ? round(($this->obtained_marks / $this->total_marks) * 100, 2)
                : null,
            'is_pass'        => $this->is_pass,
            'status'         => $this->status,
            'teacher_notes'  => $this->teacher_notes,
            'answers'        => ExamAnswerResource::collection($this->whenLoaded('answers')),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
