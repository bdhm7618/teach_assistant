<?php

namespace Modules\Payment\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period_type' => $this->period_type->value,
            'period_type_label' => $this->period_type->label(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'month' => $this->month,
            'year' => $this->year,
            'is_open' => $this->is_open,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'channel_id' => $this->channel_id,
            'total_payments' => $this->getPaymentsCount(),
            'total_amount' => (float) $this->getTotalPayments(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

