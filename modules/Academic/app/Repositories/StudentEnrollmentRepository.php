<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\StudentEnrollment;
use Prettus\Repository\Eloquent\BaseRepository;

class StudentEnrollmentRepository extends BaseRepository
{
    public function model()
    {
        return StudentEnrollment::class;
    }

    /**
     * Get enrollments by student
     */
    public function getByStudent(int $studentId)
    {
        return $this->model->where('student_id', $studentId)
            ->with(['group', 'group.subject', 'group.classGrade'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get enrollments by group
     */
    public function getByGroup(int $groupId)
    {
        return $this->model->where('group_id', $groupId)
            ->with(['student'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active enrollments
     */
    public function getActive()
    {
        return $this->model->where('status', 'active')
            ->with(['student', 'group'])
            ->get();
    }

    /**
     * Create enrollment with automatic remaining sessions calculation
     */
    public function create(array $data): StudentEnrollment
    {
        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        // Calculate remaining sessions if sessions_per_month is set
        if (isset($data['sessions_per_month'])) {
            $data['remaining_sessions_count'] = $data['sessions_per_month'];
        }

        $enrollment = $this->model->create($data);
        return $enrollment;
    }
}

