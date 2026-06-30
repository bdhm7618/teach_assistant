<?php

namespace Modules\ParentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ParentProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'image'             => $this->image ? asset('storage/' . $this->image) : null,
            'status'            => $this->status,
            'channel_id'        => $this->channel_id,
            'email_verified_at' => $this->email_verified_at,
            'children_count'    => $this->whenCounted('students'),
        ];
    }
}
