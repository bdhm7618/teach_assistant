<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\AcademicYear;
use Modules\Academic\App\Models\ClassGrade;
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
            'grade_level' => 'required|integer|min:1|max:12',
            'stage' => 'required|in:primary,preparatory,secondary',
            'academic_year_id' => [
                'required',
                $this->belongsToChannel(AcademicYear::class),
            ],
            'is_active' => 'sometimes|boolean'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $gradeLevel = $this->input('grade_level');
            $stage = $this->input('stage');
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

            // Validate uniqueness (works for both create and update)
            if ($gradeLevel && $stage) {
                $uniqueRule = $this->uniqueInChannel(
                    ClassGrade::class,
                    ['grade_level', 'stage'],
                    $classGradeId
                );

                // Validate uniqueness
                $uniqueRule->validate('grade_level', $gradeLevel, function ($message) use ($validator, $gradeLevel, $stage) {
                    $validator->errors()->add(
                        'grade_level',
                        trans('academic::app.validation.class_grade_duplicate', [
                            'grade_level' => $gradeLevel,
                            'stage' => $stage
                        ])
                    );
                });
            }
        });
    }
}
