<?php

namespace Modules\Payment\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'value' => (float) $this->value,
            'min_amount' => $this->min_amount ? (float) $this->min_amount : null,
            'max_discount' => $this->max_discount ? (float) $this->max_discount : null,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'is_active' => $this->is_active,
            'applies_to' => $this->applies_to,
            'channel_id' => $this->channel_id,
            'is_valid' => $this->isValid(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

