<?php

namespace Modules\Assignment\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'url'           => $this->url,
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
        ];
    }
}
