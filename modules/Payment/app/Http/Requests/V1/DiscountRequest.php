<?php

namespace Modules\Payment\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Payment\App\Models\Discount;

class DiscountRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('discount') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($discountId, $channelId) {
                    if ($value) {
                        $query = Discount::withoutChannelScope()
                            ->where('code', $value);

                        if ($channelId) {
                            $query->where('channel_id', $channelId);
                        }

                        if ($discountId) {
                            $query->where('id', '!=', $discountId);
                        }

                        if ($query->exists()) {
                            $fail(trans('payment::app.discount.code_exists'));
                        }
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0.01',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'applies_to' => 'nullable|in:all,groups,students',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $discountId = $this->route('discount') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // Validate percentage value
            if ($this->input('type') === 'percentage' && $this->input('value') > 100) {
                $validator->errors()->add(
                    'value',
                    trans('payment::app.discount.percentage_max')
                );
            }

            // In update case, verify that the record belongs to the current channel
            if ($discountId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $existingDiscount = Discount::withoutChannelScope()
                    ->where('id', $discountId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$existingDiscount) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                }
            }
        });
    }
}

