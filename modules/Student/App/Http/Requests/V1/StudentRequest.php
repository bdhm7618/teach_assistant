<?php

namespace Modules\Student\App\Http\Requests\V1;

use Modules\Student\App\Models\Student;
use Modules\Academic\App\Models\Group;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class StudentRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $studentId = $this->route('student') ?? $this->route('id') ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                $this->uniqueInChannel(Student::class, ['email'], $studentId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                $this->uniqueInChannel(Student::class, ['phone'], $studentId),
            ],
            'gender' => 'required|in:male,female',
            'status' => 'sometimes|integer|in:0,1',
            'image' => 'nullable|string|max:500',
            'group_ids' => 'sometimes|array',
            'group_ids.*' => [
                "required",
                "integer",
                $this->belongsToChannel(Group::class),
            ],
        ];

        // Password is required only on create
        if (!$isUpdate) {
            $rules['password'] = 'nullable|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        // Code is optional, will be auto-generated if not provided
        if ($isUpdate) {
            $rules['code'] = [
                'sometimes',
                'string',
                'max:255',
                $this->uniqueInChannel(Student::class, ['code'], $studentId),
            ];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $studentId = $this->route('student') ?? $this->route('id') ?? null;

            // In update case, verify that the record belongs to the current channel
            if ($studentId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $channelId = $this->getChannelId();
                if ($channelId) {
                    $existingStudent = Student::withoutChannelScope()
                        ->where('id', $studentId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingStudent) {
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
