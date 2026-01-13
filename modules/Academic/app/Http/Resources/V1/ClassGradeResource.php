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
            'academic_year_id' => $this->academic_year_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'channel_id' => $this->channel_id,
        ];
    }
}
