<?php

namespace Modules\Student\App\Repositories;

use Modules\Student\App\Models\Student;
use Prettus\Repository\Eloquent\BaseRepository;

class StudentRepository extends BaseRepository
{
    public function model()
    {
        return Student::class;
    }
}

