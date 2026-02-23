<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\Level;
use Prettus\Repository\Eloquent\BaseRepository;

class LevelRepository extends BaseRepository
{
    public function model()
    {
        return Level::class;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
}

