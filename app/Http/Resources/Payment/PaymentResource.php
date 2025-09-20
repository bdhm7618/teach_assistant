<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'student_id'        => $this->student_id,
            'teacher_id'        => $this->teacher_id,
            'payment_month_id'  => $this->payment_month_id,
            'amount'            => $this->amount,
            'discount'          => $this->discount,
            'currency'          => $this->currency,
            'status'            => $this->status,
            'meta'              => $this->meta,
            'paid_at'           => $this->paid_at?->toDateTimeString(),
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
