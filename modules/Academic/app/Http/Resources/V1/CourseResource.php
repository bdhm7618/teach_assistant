<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'type'            => $this->type,
            'status'          => $this->status,
            'description'     => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'subject'         => $this->whenLoaded('subject', fn() => new SubjectResource($this->subject)),
            'groups_count'    => $this->when(isset($this->groups_count), $this->groups_count),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
