<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\AcademicYear;
use Prettus\Repository\Eloquent\BaseRepository;


class AcademicYearRepository extends BaseRepository
{
    public function model()
    {
        return AcademicYear::class;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
}
