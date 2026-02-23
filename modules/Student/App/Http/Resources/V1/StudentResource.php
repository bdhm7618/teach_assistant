<?php

namespace Modules\Student\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'image' => $this->getImageUrl(),
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'channel_id' => $this->channel_id,
            'channel' => $this->whenLoaded('channel', function () {
                return [
                    'id' => $this->channel->id,
                    'name' => $this->channel->name,
                    'code' => $this->channel->code,
                ];
            }),
            'groups' => $this->whenLoaded('groups', function () {
                return $this->groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'code' => $group->code,
                    ];
                });
            }),
            'groups_count' => $this->when(isset($this->groups_count), $this->groups_count),
            'attendances_count' => $this->when(isset($this->attendances_count), $this->attendances_count),
            'payments_count' => $this->when(isset($this->payments_count), $this->payments_count),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
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

