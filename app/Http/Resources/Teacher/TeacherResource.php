<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use App\Http\Resources\Channel\ChannelResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'gender'         => $this->gender,
            'status'         => $this->status,
            'image'          => $this->image,
            'channel'        => new ChannelResource($this->whenLoaded('channel')),
            'email_verified' => $this->email_verified_at ? true : false,
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
