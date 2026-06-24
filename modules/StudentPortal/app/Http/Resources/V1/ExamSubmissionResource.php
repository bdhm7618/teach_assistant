<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'exam_id'        => $this->exam_id,
            'attempt_number' => $this->attempt_number,
            'status'         => $this->status,
            'started_at'     => $this->started_at,
            'submitted_at'   => $this->submitted_at,
            'total_marks'    => $this->total_marks,
            'obtained_marks' => $this->obtained_marks,
            'is_pass'        => $this->is_pass,
            'teacher_notes'  => $this->teacher_notes,
            'exam'           => $this->whenLoaded('exam', fn() => [
                'id'    => $this->exam->id,
                'title' => $this->exam->title,
            ]),
            'answers' => $this->whenLoaded('answers', fn() =>
                $this->answers->map(fn($a) => [
                    'id'                 => $a->id,
                    'question_id'        => $a->question_id,
                    'selected_option_id' => $a->selected_option_id,
                    'answer_text'        => $a->answer_text,
                    'is_correct'         => $a->is_correct,
                    'marks_obtained'     => $a->marks_obtained,
                ])
            ),
        ];
    }
}
