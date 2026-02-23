<?php

namespace Modules\Payment\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'student_id' => $this->student_id,
            'student' => [
                'id' => $this->student->id ?? null,
                'name' => $this->student->name ?? null,
                'code' => $this->student->code ?? null,
            ],
            'group_id' => $this->group_id,
            'group' => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'code' => $this->group->code,
            ] : null,
            'total_amount' => (float) $this->total_amount,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'final_amount' => (float) $this->final_amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'channel_id' => $this->channel_id,
            'is_fully_paid' => $this->isFullyPaid(),
            'is_overdue' => $this->isOverdue(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'installments' => InstallmentResource::collection($this->whenLoaded('installments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

