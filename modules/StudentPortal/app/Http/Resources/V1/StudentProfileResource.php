<?php

namespace Modules\StudentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'code'               => $this->code,
            'name'               => $this->name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'gender'             => $this->gender,
            'image'              => $this->image ? asset('storage/' . $this->image) : null,
            'status'             => $this->status,
            'channel_id'         => $this->channel_id,
            'email_verified_at'  => $this->email_verified_at,
        ];
    }
}
