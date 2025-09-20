<?php

namespace App\Http\Resources\Student;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'geneder'         => $this->geneder,
            'status'          => $this->status,
            'group_id'      => $this->group_id,
            "channel_id" => $this->channel_id,
            // 'image'           => $this->image,
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}
