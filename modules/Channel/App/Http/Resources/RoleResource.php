<?php

namespace Modules\Channel\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions ? (is_string($this->permissions) ? json_decode($this->permissions, true) : $this->permissions) : [],
            'channel_id' => $this->channel_id,
            'is_general' => $this->isGeneral(),
            'is_channel_specific' => $this->isChannelSpecific(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

