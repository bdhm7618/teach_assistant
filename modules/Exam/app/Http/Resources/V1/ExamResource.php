<?php

namespace Modules\Exam\App\Http\Resources\V1;

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
            'starts_at'        => $this->starts_at?->toISOString(),
            'ends_at'          => $this->ends_at?->toISOString(),
            'group_id'         => $this->group_id,
            'course_id'        => $this->course_id,
            'questions_count'  => $this->whenCounted('questions'),
            'submissions_count'=> $this->whenCounted('submissions'),
            'questions'        => ExamQuestionResource::collection($this->whenLoaded('questions')),
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
