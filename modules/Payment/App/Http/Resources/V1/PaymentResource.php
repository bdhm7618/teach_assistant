<?php

namespace Modules\Payment\App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'payment_period_id' => $this->payment_period_id,
            'payment_period' => $this->paymentPeriod ? [
                'id' => $this->paymentPeriod->id,
                'name' => $this->paymentPeriod->name,
                'period_type' => $this->paymentPeriod->period_type->value,
            ] : null,
            'invoice_id' => $this->invoice_id,
            'installment_id' => $this->installment_id,
            'amount' => (float) $this->amount,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'final_amount' => (float) $this->final_amount,
            'payment_date' => $this->payment_date?->format('Y-m-d H:i:s'),
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'reference_number' => $this->reference_number,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'paid_by' => $this->paid_by,
            'recorder' => $this->recorder ? [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ] : null,
            'channel_id' => $this->channel_id,
            'is_completed' => $this->isCompleted(),
            'can_be_refunded' => $this->canBeRefunded(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

