<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class SessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at'              => 'required_without:recurring_rule|date|after:now',
            'duration_minutes'          => 'sometimes|integer|min:15|max:480',
            'type'                      => 'sometimes|in:online,offline',
            'location'                  => 'nullable|string|max:255',
            'notes'                     => 'nullable|string',
            'recurring_rule'            => 'sometimes|array',
            'recurring_rule.day'        => 'required_with:recurring_rule|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'recurring_rule.start_time' => 'required_with:recurring_rule|date_format:H:i',
            'recurring_rule.end_time'   => 'nullable|date_format:H:i|after:recurring_rule.start_time',
        ];
    }
}
