<?php

namespace Modules\Payment\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'installment_number' => $this->installment_number,
            'amount' => (float) $this->amount,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'channel_id' => $this->channel_id,
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            'paid_amount' => (float) $this->getPaidAmount(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

