<?php

namespace Modules\Payment\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Payment\App\Enums\PaymentStatus;
use Modules\Payment\App\Enums\PaymentMethod;
use Modules\Student\App\Models\Student;
use Modules\Academic\App\Models\Group;
use Modules\Payment\App\Models\Payment;
use Illuminate\Validation\Rule;

class PaymentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentId = $this->route('payment') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

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
            'payment_period_id' => 'nullable|integer|exists:payment_periods,id',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            'installment_id' => 'nullable|integer|exists:installments,id',
            'amount' => 'required|numeric|min:0.01',
            'discount_amount' => 'nullable|numeric|min:0|max:' . ($this->input('amount') ?? 0),
            'payment_date' => 'nullable|date',
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', 'string', Rule::enum(PaymentStatus::class)],
            'reference_number' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $paymentId = $this->route('payment') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // Validate reference number for methods that require it
            $paymentMethod = $this->input('payment_method');
            if ($paymentMethod && PaymentMethod::from($paymentMethod)->requiresReference()) {
                if (!$this->input('reference_number')) {
                    $validator->errors()->add(
                        'reference_number',
                        trans('payment::app.validation.reference_required')
                    );
                }
            }

            // In update case, verify that the record belongs to the current channel
            if ($paymentId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $existingPayment = Payment::withoutChannelScope()
                    ->where('id', $paymentId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$existingPayment) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                }
            }
        });
    }
}

