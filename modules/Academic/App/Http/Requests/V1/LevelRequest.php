<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\Level;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class LevelRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levelId = $this->route('level') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($levelId, $channelId) {
                    $exists = Level::withoutChannelScope()
                        ->where('name', $value)
                        ->where('channel_id', $channelId)
                        ->when($levelId, fn($q) => $q->where('id', '!=', $levelId))
                        ->exists();
                    
                    if ($exists) {
                        $fail(trans('academic::app.validation.level_name_duplicate', ['name' => $value]));
                    }
                },
            ],
            'code' => 'nullable|string|max:50',
            'level_number' => 'nullable|integer|min:1|max:12',
            'stage' => 'nullable|in:primary,preparatory,secondary',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $levelId = $this->route('level') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // In update case, verify that the record belongs to the current channel
            if ($levelId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                if ($channelId) {
                    $existingLevel = Level::withoutChannelScope()
                        ->where('id', $levelId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingLevel) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }

                    // Prevent editing system default levels
                    if ($existingLevel->is_default && $existingLevel->channel_id === null) {
                        $validator->errors()->add(
                            'id',
                            trans('academic::app.validation.cannot_edit_default_level')
                        );
                        return;
                    }
                }
            }
        });
    }
}

