<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'level_number' => $this->level_number,
            'stage' => $this->stage,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'channel_id' => $this->channel_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

