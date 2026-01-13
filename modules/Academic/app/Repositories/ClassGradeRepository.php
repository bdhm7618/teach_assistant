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

    public function create(array $data): ClassGrade
    {
        $data['channel_id'] = auth("user")->user()?->channel_id;
        return $this->model->create($data);
    }
}
