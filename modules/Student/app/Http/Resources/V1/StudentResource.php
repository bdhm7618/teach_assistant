<?php

namespace Modules\Student\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'image' => $this->image,
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
            'guardians' => $this->whenLoaded('guardians', fn () =>
                $this->guardians->map(fn ($g) => [
                    'id'           => $g->id,
                    'name'         => $g->name,
                    'phone'        => $g->phone,
                    'relationship' => $g->relationship,
                    'is_primary'   => (bool) $g->is_primary,
                ])
            ),
            'primary_guardian' => $this->whenLoaded('primaryGuardian', fn () =>
                $this->primaryGuardian ? [
                    'id'    => $this->primaryGuardian->id,
                    'name'  => $this->primaryGuardian->name,
                    'phone' => $this->primaryGuardian->phone,
                ] : null
            ),
            'groups_count' => $this->when(isset($this->groups_count), $this->groups_count),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

