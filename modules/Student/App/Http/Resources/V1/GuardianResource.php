<?php

namespace Modules\Student\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'student_id'   => $this->student_id,
            'name'         => $this->name,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'relationship' => $this->relationship,
            'is_primary'   => $this->is_primary,
            'notes'        => $this->notes,
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
