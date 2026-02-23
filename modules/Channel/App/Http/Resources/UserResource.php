<?php

namespace Modules\Channel\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'image' => $this->getImageUrl(),
            'channel_id' => $this->channel_id,
            'role_id' => $this->role_id,
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'description' => $this->role?->description,
            ],
            'channel' => [
                'id' => $this->channel?->id,
                'name' => $this->channel?->name,
                'code' => $this->channel?->code,
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Get the full URL for the image
     */
    protected function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // If already a full URL, return as is
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // If starts with /storage, use asset helper
        if (str_starts_with($this->image, '/storage/')) {
            return asset($this->image);
        }

        // If starts with storage/, use Storage::url
        if (str_starts_with($this->image, 'storage/')) {
            return Storage::disk('public')->url($this->image);
        }

        // Otherwise, assume it's in public storage
        return Storage::disk('public')->url($this->image);
    }
}
