<?php

namespace Modules\Academic\App\Http\Requests\V1;

use Modules\Academic\App\Models\ClassGrade;
use Modules\Academic\App\Models\Subject;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\SessionTime;
use Modules\Academic\App\Repositories\SessionTimeRepository;
use Modules\Student\App\Models\Student;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class GroupRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $groupId = $this->route('group') ?? $this->route('id') ?? null;

        return [
            'name' => 'required|string|max:255',
            'class_grade_id' => [
                'required',
                $this->belongsToChannel(ClassGrade::class),
            ],
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                function ($attribute, $value, $fail) {
                    $channelId = $this->getChannelId();
                    if ($channelId) {
                        // Check if subject belongs to channel or is general (channel_id = null)
                        $subject = \Modules\Academic\App\Models\Subject::withoutChannelScope()
                            ->where('id', $value)
                            ->where(function ($query) use ($channelId) {
                                $query->where('channel_id', $channelId)
                                      ->orWhereNull('channel_id');
                            })
                            ->first();
                        
                        if (!$subject) {
                            $fail(trans('channel::app.validation.model_not_belongs_to_channel'));
                        }
                    }
                },
            ],
            'capacity' => 'required|integer|min:1|max:100',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'code' => [
                'sometimes',
                'string',
                'max:255',
                $this->uniqueInChannel(Group::class, ['code'], $groupId),
            ],
            'student_ids' => 'sometimes|array',
            'student_ids.*' => [
                $this->belongsToChannel(Student::class),
            ],
            'session_times' => 'sometimes|array',
            'session_times.*.day' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'session_times.*.start_time' => 'required|date_format:H:i',
            'session_times.*.end_time' => 'required|date_format:H:i|after:session_times.*.start_time',
            'session_times.*.is_active' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $groupId = $this->route('group') ?? $this->route('id') ?? null;

            // In update case, verify that the record belongs to the current channel
            if ($groupId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $channelId = $this->getChannelId();
                if ($channelId) {
                    $existingGroup = Group::withoutChannelScope()
                        ->where('id', $groupId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingGroup) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }
                }
            }

            // Validate uniqueness of name within class_grade and subject
            $name = $this->input('name');
            $classGradeId = $this->input('class_grade_id');
            $subjectId = $this->input('subject_id');

            if ($name && $classGradeId && $subjectId) {
                $uniqueRule = $this->uniqueInChannel(
                    Group::class,
                    ['name', 'class_grade_id', 'subject_id'],
                    $groupId
                );

                $uniqueRule->validate('name', $name, function ($message) use ($validator, $name) {
                    $validator->errors()->add(
                        'name',
                        trans('academic::app.validation.group_duplicate', [
                            'name' => $name
                        ])
                    );
                });
            }

            // Validate session times for conflicts
            $sessionTimes = $this->input('session_times', []);
            if (!empty($sessionTimes)) {
                $this->validateSessionTimes($validator, $sessionTimes, $groupId);
            }
        });
    }

    /**
     * Validate session times for conflicts
     */
    protected function validateSessionTimes($validator, $sessionTimes, $groupId = null)
    {
        $channelId = $this->getChannelId();
        if (!$channelId) {
            return;
        }

        // Check for conflicts within the same request
        $this->checkInternalConflicts($validator, $sessionTimes);

        // Check for conflicts with existing sessions
        $this->checkExternalConflicts($validator, $sessionTimes, $groupId, $channelId);
    }

    /**
     * Check for conflicts within the same request (same group)
     */
    protected function checkInternalConflicts($validator, $sessionTimes)
    {
        $days = [];
        foreach ($sessionTimes as $index => $sessionTime) {
            $day = $sessionTime['day'] ?? null;
            $startTime = $sessionTime['start_time'] ?? null;
            $endTime = $sessionTime['end_time'] ?? null;

            if (!$day || !$startTime || !$endTime) {
                continue;
            }

            // Check if end_time is after start_time
            if (strtotime($endTime) <= strtotime($startTime)) {
                $validator->errors()->add(
                    "session_times.{$index}.end_time",
                    trans('academic::app.validation.end_time_after_start_time')
                );
                continue;
            }

            // Group by day
            if (!isset($days[$day])) {
                $days[$day] = [];
            }

            // Check for conflicts with other sessions on the same day
            foreach ($days[$day] as $existingIndex => $existingSession) {
                if ($this->timesOverlap($startTime, $endTime, $existingSession['start_time'], $existingSession['end_time'])) {
                    $validator->errors()->add(
                        "session_times.{$index}.start_time",
                        trans('academic::app.validation.session_time_conflict', [
                            'day' => ucfirst($day),
                            'time' => "{$existingSession['start_time']} - {$existingSession['end_time']}"
                        ])
                    );
                }
            }

            $days[$day][] = [
                'index' => $index,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }
    }

    /**
     * Check for conflicts with existing sessions in database
     */
    protected function checkExternalConflicts($validator, $sessionTimes, $groupId, $channelId)
    {
        $sessionTimeRepository = app(SessionTimeRepository::class);
        
        foreach ($sessionTimes as $index => $sessionTime) {
            $day = $sessionTime['day'] ?? null;
            $startTime = $sessionTime['start_time'] ?? null;
            $endTime = $sessionTime['end_time'] ?? null;

            if (!$day || !$startTime || !$endTime) {
                continue;
            }

            // Query existing sessions that might conflict using repository
            $query = $sessionTimeRepository->makeModel()
                ->withoutChannelScope()
                ->where('channel_id', $channelId)
                ->where('day', $day)
                ->where('is_active', true);

            // Exclude current group's sessions if updating
            if ($groupId) {
                $query->where('group_id', '!=', $groupId);
            }

            $conflictingSessions = $query->get();

            foreach ($conflictingSessions as $existingSession) {
                if ($this->timesOverlap($startTime, $endTime, $existingSession->start_time, $existingSession->end_time)) {
                    $group = $existingSession->group;
                    $validator->errors()->add(
                        "session_times.{$index}.start_time",
                        trans('academic::app.validation.session_time_conflict_existing', [
                            'day' => ucfirst($day),
                            'time' => "{$existingSession->start_time} - {$existingSession->end_time}",
                            'group' => $group ? $group->name : 'Unknown'
                        ])
                    );
                }
            }
        }
    }

    /**
     * Check if two time ranges overlap
     */
    protected function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        $start1Time = strtotime($start1);
        $end1Time = strtotime($end1);
        $start2Time = strtotime($start2);
        $end2Time = strtotime($end2);

        return ($start1Time < $end2Time && $end1Time > $start2Time);
    }
}

