<?php

namespace App\Http\Resources\Group;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'code'               => $this->code,
            'class_id'           => $this->class_id,
            'numbre_of_sessions' => $this->numbre_of_sessions,
            'price_of_group'     => $this->price_of_group,
            'status'             => $this->status,
            'channel_id'         => $this->channel_id,
            'teacher_id'         => $this->teacher_id,
            'created_at'         => $this->created_at?->toDateTimeString(),
            'updated_at'         => $this->updated_at?->toDateTimeString(),
        ];
    }
}
