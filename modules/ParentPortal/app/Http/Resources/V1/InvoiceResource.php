<?php

namespace Modules\ParentPortal\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'invoice_number'   => $this->invoice_number,
            'type'             => $this->type,
            'status'           => $this->status,
            'total_amount'     => $this->total_amount,
            'discount_amount'  => $this->discount_amount,
            'final_amount'     => $this->final_amount,
            'paid_amount'      => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'due_date'         => $this->due_date?->toDateString(),
            'issue_date'       => $this->issue_date?->toDateString(),
            'reason'           => $this->reason,
            'notes'            => $this->notes,
            'group'            => $this->whenLoaded('group', fn() => [
                'id'   => $this->group->id,
                'name' => $this->group->name,
            ]),
        ];
    }
}
