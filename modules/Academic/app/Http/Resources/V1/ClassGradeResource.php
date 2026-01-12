<?php

namespace Modules\Academic\App\Http\Resources\V1;


use Illuminate\Http\Resources\Json\JsonResource;

class ClassGradeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'grade_level' => $this->grade_level,
            'stage' => $this->stage,
            'display_name' => $this->display_name,
            'academic_year' => [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name
            ],
            'is_active' => $this->is_active
        ];
    }
}
