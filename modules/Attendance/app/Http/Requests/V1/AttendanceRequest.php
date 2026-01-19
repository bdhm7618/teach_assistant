<?php

namespace Modules\Attendance\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;
use Modules\Attendance\App\Enums\AttendanceStatus;
use Modules\Attendance\App\Repositories\AttendanceRepository;
use Carbon\Carbon;

class AttendanceRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $attendanceId = $this->route('attendance') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
                $this->belongsToChannel(\Modules\Student\App\Models\Student::class),
            ],
            'group_id' => [
                'required',
                'integer',
                'exists:groups,id',
                $this->belongsToChannel(\Modules\Academic\App\Models\Group::class),
            ],
            'session_time_id' => 'nullable|integer|exists:session_times,id',
            'date' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', AttendanceStatus::values()),
            ],
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $studentId = $this->input('student_id');
            $groupId = $this->input('group_id');
            $date = $this->input('date');
            $attendanceId = $this->route('attendance') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // Verify student belongs to the group
            if ($studentId && $groupId) {
                $student = \Modules\Student\App\Models\Student::find($studentId);
                if ($student && !$student->groups()->where('groups.id', $groupId)->exists()) {
                    $validator->errors()->add(
                        'student_id',
                        trans('attendance::app.validation.student_not_in_group')
                    );
                }
            }

            // Check for duplicate attendance record (only on create)
            if (!$attendanceId && $studentId && $groupId && $date) {
                $repository = app(AttendanceRepository::class);
                if ($repository->exists($studentId, $groupId, $date, $channelId)) {
                    $validator->errors()->add(
                        'date',
                        trans('attendance::app.validation.duplicate_attendance')
                    );
                }
            }

            // Verify attendance record belongs to current channel (on update)
            if ($attendanceId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                $attendance = \Modules\Attendance\App\Models\Attendance::withoutChannelScope()
                    ->where('id', $attendanceId)
                    ->where('channel_id', $channelId)
                    ->first();

                if (!$attendance) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                }
            }
        });
    }

    /**
     * Get validated data with additional processing
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        
        // Ensure channel_id is set
        if (!isset($data['channel_id'])) {
            $data['channel_id'] = $this->getChannelId();
        }

        // Convert date string to Carbon if needed
        if (isset($data['date']) && is_string($data['date'])) {
            $data['date'] = Carbon::parse($data['date']);
        }

        return $data;
    }
}

