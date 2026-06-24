<?php

namespace Modules\Exam\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamAnswerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'question_id'        => $this->question_id,
            'selected_option_id' => $this->selected_option_id,
            'answer_text'        => $this->answer_text,
            'marks_obtained'     => $this->marks_obtained,
            'is_correct'         => $this->is_correct,
            'question'           => new ExamQuestionResource($this->whenLoaded('question')),
            'selected_option'    => new ExamOptionResource($this->whenLoaded('selectedOption')),
        ];
    }
}
