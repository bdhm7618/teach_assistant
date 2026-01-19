<?php

namespace Modules\Payment\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Payment\App\Enums\PaymentPeriodType;
use Modules\Payment\App\Models\PaymentPeriod;
use Illuminate\Validation\Rule;

class PaymentPeriodRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $periodId = $this->route('period') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'name' => 'nullable|string|max:255',
            'period_type' => ['required', 'string', Rule::enum(PaymentPeriodType::class)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2100',
            'is_open' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $periodId = $this->route('period') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();
            $periodType = $this->input('period_type');

            // Validate month and year for monthly periods
            if ($periodType === PaymentPeriodType::MONTHLY->value) {
                if (!$this->input('month') || !$this->input('year')) {
                    $validator->errors()->add(
                        'month',
                        trans('payment::app.period.month_year_required')
                    );
                }
            }

            // In update case, verify that the record belongs to the current channel
            if ($periodId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $existingPeriod = PaymentPeriod::withoutChannelScope()
                    ->where('id', $periodId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$existingPeriod) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                }
            }
        });
    }
}

