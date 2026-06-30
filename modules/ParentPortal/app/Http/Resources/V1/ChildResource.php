<?php

namespace Modules\ParentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ChildResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'code'         => $this->code,
            'name'         => $this->name,
            'gender'       => $this->gender,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'image'        => $this->image ? asset('storage/' . $this->image) : null,
            'status'       => $this->status,
            // Pivot data (present when loaded via the parent->students() relation).
            'relationship' => $this->whenPivotLoaded('parent_student', fn() => $this->pivot->relationship),
            'is_primary'   => $this->whenPivotLoaded('parent_student', fn() => (bool) $this->pivot->is_primary),
        ];
    }
}
