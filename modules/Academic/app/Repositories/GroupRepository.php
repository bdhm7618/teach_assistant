<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\Group;
use Prettus\Repository\Eloquent\BaseRepository;

class GroupRepository extends BaseRepository
{
    public function model()
    {
        return Group::class;
    }

    /**
     * Find group by class grade ID
     */
    public function findByClassGradeId($classGradeId)
    {
        return $this->model->where('class_grade_id', $classGradeId)->get();
    }

    /**
     * Count groups by class grade ID
     */
    public function countByClassGradeId($classGradeId): int
    {
        return $this->model->where('class_grade_id', $classGradeId)->count();
    }

    /**
     * Check if code exists
     */
    public function codeExists(string $code): bool
    {
        return $this->model->where('code', $code)->exists();
    }
}
