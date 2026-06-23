<?php

namespace Modules\Exam\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'exam_id'     => $this->exam_id,
            'question'    => $this->question,
            'type'        => $this->type,
            'marks'       => $this->marks,
            'order'       => $this->order,
            'explanation' => $this->when(
                $this->relationLoaded('options') || $request->routeIs('*result*'),
                $this->explanation
            ),
            'options'     => ExamOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
