<?php

namespace Modules\Academic\App\Http\Resources\V1;


use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'is_active' => $this->is_active,
        ];
    }
}
