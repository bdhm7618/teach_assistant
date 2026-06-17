<?php

namespace Modules\Payment\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Student\App\Models\Student;
use Modules\Academic\App\Models\Group;
use Modules\Payment\App\Models\Invoice;

class InvoiceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice') ?? $this->route('id') ?? null;

        return [
            'student_id' => [
                'required',
                'integer',
                $this->belongsToChannel(Student::class),
            ],
            'group_id' => [
                'nullable',
                'integer',
                $this->belongsToChannel(Group::class),
            ],
            'enrollment_id'   => 'nullable|integer|exists:student_enrollments,id',
            'total_amount'    => 'required|numeric|min:0.01',
            'discount_amount' => 'nullable|numeric|min:0|max:' . ($this->input('total_amount') ?? 0),
            'due_date'        => 'required|date|after_or_equal:today',
            'issue_date'      => 'nullable|date',
            'type'            => 'nullable|in:monthly,session,enrollment_fee,ad_hoc',
            'reason'          => 'required_if:type,ad_hoc|nullable|string|max:500',
            'notes'           => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $invoiceId = $this->route('invoice') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // In update case, verify that the record belongs to the current channel
            if ($invoiceId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $existingInvoice = Invoice::withoutChannelScope()
                    ->where('id', $invoiceId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$existingInvoice) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                }
            }
        });
    }
}

