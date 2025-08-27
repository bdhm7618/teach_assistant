<?php

namespace App\Http\Resources\Class;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'start_year' => $this->start_year,
            'end_year'   => $this->end_year,
            'name'       => $this->name,
            'code'       => $this->code,
            'status'     => $this->status,
            'channel_id' => $this->channel_id,
            'subject_id' => $this->subject_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
