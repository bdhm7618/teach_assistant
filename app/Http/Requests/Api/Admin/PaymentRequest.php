<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id'        => 'required|exists:students,id',
            'payment_month_id'  => 'required|exists:payment_months,id',
            'amount'            => 'required|numeric|min:0.01',
            'discount'          => 'nullable|numeric|min:0',
            'currency'          => 'required|string|size:3',
            'status'            => 'nullable|integer|in:0,1,2,3',
            'meta'              => 'nullable|array',
            'paid_at'           => 'nullable|date',
        ];
    }
}
