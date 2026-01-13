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
        $data['channel_id'] = auth("user")->user()?->channel_id;
        return $this->model->create($data);
    }
}
