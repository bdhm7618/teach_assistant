<?php

namespace Modules\Exam\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamOptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'text'       => $this->text,
            'order'      => $this->order,
            // is_correct is hidden during exam attempt — only shown after grading
            'is_correct' => $this->when(
                $request->routeIs('*result*') || $request->routeIs('*question*'),
                $this->is_correct
            ),
        ];
    }
}
