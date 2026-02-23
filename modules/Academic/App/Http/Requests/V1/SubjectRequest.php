<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\Subject;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class SubjectRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $subjectId = $this->route('subject') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($subjectId, $channelId) {
                    $query = Subject::withoutChannelScope()
                        ->where('code', $value);
                    
                    if ($subjectId) {
                        $query->where('id', '!=', $subjectId);
                    }
                    
                    // Check uniqueness: code must be unique within channel OR globally if channel_id is null
                    $exists = $query->where(function ($q) use ($channelId) {
                        $q->where('channel_id', $channelId)
                          ->orWhereNull('channel_id');
                    })->exists();
                    
                    if ($exists) {
                        $fail(trans('academic::app.validation.subject_code_duplicate', ['code' => $value]));
                    }
                },
            ],
            'credits' => 'required|integer|min:0|max:10',
            'is_active' => 'sometimes|boolean',
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.en.description' => 'nullable|string',
            'translations.ar' => 'required|array',
            'translations.ar.name' => 'required|string|max:255',
            'translations.ar.description' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $subjectId = $this->route('subject') ?? $this->route('id') ?? null;

            // In update case, verify that the record belongs to the current channel
            if ($subjectId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $channelId = $this->getChannelId();
                if ($channelId) {
                    $existingSubject = Subject::withoutChannelScope()
                        ->where('id', $subjectId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingSubject) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }
                }
            }
        });
    }
}
