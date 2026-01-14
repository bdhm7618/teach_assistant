<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\Subject;
use Prettus\Repository\Eloquent\BaseRepository;

class SubjectRepository extends BaseRepository
{
    public function model()
    {
        return Subject::class;
    }
}

