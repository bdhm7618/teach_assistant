<?php

namespace Modules\Attendance\App\Repositories;

use Modules\Attendance\App\Models\Attendance;
use Modules\Attendance\App\Enums\AttendanceStatus;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class AttendanceRepository extends BaseRepository
{
    public function model()
    {
        return Attendance::class;
    }

    /**
     * Create attendance record
     *
     * @param array $data
     * @return Attendance
     */
    public function create(array $data): Attendance
    {
        // Ensure status is set
        if (!isset($data['status'])) {
            $data['status'] = AttendanceStatus::PRESENT->value;
        }

        // Ensure date is set to now if not provided
        if (!isset($data['date'])) {
            $data['date'] = Carbon::now();
        }

        return $this->model->create($data);
    }

    /**
     * Bulk create attendance records
     *
     * @param array $attendances Array of attendance data
     * @return Collection
     */
    public function bulkCreate(array $attendances): Collection
    {
        $records = [];
        $now = Carbon::now();

        foreach ($attendances as $attendance) {
            if (!isset($attendance['status'])) {
                $attendance['status'] = AttendanceStatus::PRESENT->value;
            }
            if (!isset($attendance['date'])) {
                $attendance['date'] = Carbon::now();
            }
            if (is_string($attendance['date'])) {
                $attendance['date'] = Carbon::parse($attendance['date']);
            }
            $attendance['created_at'] = $now;
            $attendance['updated_at'] = $now;
            $records[] = $attendance;
        }

        $this->model->insert($records);
        
        // Return the created records by querying with the same criteria
        $firstRecord = $records[0] ?? [];
        $query = $this->model->where('created_at', $now);
        
        if (isset($firstRecord['channel_id'])) {
            $query->where('channel_id', $firstRecord['channel_id']);
        }
        
        return $query->get();
    }

    /**
     * Get attendance statistics for a student
     *
     * @param int $studentId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getStudentStatistics(int $studentId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->model->where('student_id', $studentId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $total = $query->count();
        $present = $query->clone()->byStatus(AttendanceStatus::PRESENT)->count();
        $absent = $query->clone()->byStatus(AttendanceStatus::ABSENT)->count();
        $late = $query->clone()->byStatus(AttendanceStatus::LATE)->count();
        $excused = $query->clone()->byStatus(AttendanceStatus::EXCUSED)->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'attendance_rate' => $total > 0 ? round((($present + $excused) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get attendance statistics for a group
     *
     * @param int $groupId
     * @param Carbon|null $date
     * @return array
     */
    public function getGroupStatistics(int $groupId, ?Carbon $date = null): array
    {
        $query = $this->model->where('group_id', $groupId);

        if ($date) {
            $query->whereDate('date', $date);
        }

        $total = $query->count();
        $present = $query->clone()->byStatus(AttendanceStatus::PRESENT)->count();
        $absent = $query->clone()->byStatus(AttendanceStatus::ABSENT)->count();
        $late = $query->clone()->byStatus(AttendanceStatus::LATE)->count();
        $excused = $query->clone()->byStatus(AttendanceStatus::EXCUSED)->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'attendance_rate' => $total > 0 ? round((($present + $excused) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Check if attendance record already exists
     *
     * @param int $studentId
     * @param int $groupId
     * @param Carbon|string $date
     * @param int|null $channelId
     * @return bool
     */
    public function exists(int $studentId, int $groupId, Carbon|string $date, ?int $channelId = null): bool
    {
        $query = $this->model->where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->whereDate('date', $date);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->exists();
    }
}

