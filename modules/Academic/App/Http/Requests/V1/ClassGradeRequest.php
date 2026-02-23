<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\ClassGrade;
use Modules\Academic\App\Models\Level;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ClassGradeRequest extends BaseRequest
{
    public function authorize()
    {
        return true; // Add policies if needed
    }

    public function rules()
    {
        return [
            'level_id' => [
                'required',
                'integer',
                'exists:levels,id',
                function ($attribute, $value, $fail) {
                    $channelId = $this->getChannelId();
                    $level = Level::availableForChannel($channelId)->find($value);
                    if (!$level) {
                        $fail(trans('academic::app.validation.level_not_available'));
                    }
                },
            ],
            'name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $levelId = $this->input('level_id');
            $classGradeId = $this->route('class_grade') ?? $this->route('id') ?? null;

            // In update case, verify that the record belongs to the current channel
            if ($classGradeId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $channelId = $this->getChannelId();
                if ($channelId) {
                    $existingClassGrade = ClassGrade::withoutChannelScope()
                        ->where('id', $classGradeId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingClassGrade) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }
                }
            }

            // Validate uniqueness: one class grade per level per channel
            if ($levelId) {
                $uniqueRule = $this->uniqueInChannel(
                    ClassGrade::class,
                    ['level_id'],
                    $classGradeId
                );

                $uniqueRule->validate('level_id', $levelId, function ($message) use ($validator) {
                    $validator->errors()->add(
                        'level_id',
                        trans('academic::app.validation.class_grade_level_duplicate')
                    );
                });
            }
        });
    }
}
