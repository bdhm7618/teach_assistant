<?php

namespace Modules\Academic\App\Http\Resources\V1;


use Illuminate\Http\Resources\Json\JsonResource;

class ClassGradeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level_id' => $this->level_id,
            'level' => $this->whenLoaded('level', fn() => [
                'id' => $this->level->id,
                'name' => $this->level->name,
                'code' => $this->level->code,
                'level_number' => $this->level->level_number,
                'stage' => $this->level->stage,
            ]),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'channel_id' => $this->channel_id,
        ];
    }
}
