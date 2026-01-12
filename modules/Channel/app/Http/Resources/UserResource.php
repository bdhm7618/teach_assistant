<?php

namespace Modules\Channel\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'status' => $this->status,
            "email_verified_at" => $this->email_verified_at,
            'channel_id' => $this->channel_id,
            'role_id' => $this->role_id,
            'role' => $this->role?->name,
            'channel' => $this->channel?->name,
            "channel_code" => $this->channel->code,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
