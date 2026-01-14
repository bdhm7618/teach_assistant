<?php

namespace Modules\Academic\App\Repositories;


use Modules\Academic\App\Models\ClassGrade;
use Prettus\Repository\Eloquent\BaseRepository;

class ClassGradeRepository extends BaseRepository
{
    public function model()
    {
        return  ClassGrade::class;
    }

    /**
     * Find class grade by ID
     */
    public function findById($id)
    {
        return $this->find($id);
    }
}
