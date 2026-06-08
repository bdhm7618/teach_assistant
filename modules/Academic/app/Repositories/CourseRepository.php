<?php

namespace Modules\Academic\App\Repositories;

use Modules\Academic\App\Models\Course;
use Prettus\Repository\Eloquent\BaseRepository;

class CourseRepository extends BaseRepository
{
    public function model(): string
    {
        return Course::class;
    }

    public function create(array $data): Course
    {
        $data['channel_id'] = app()->has('current_channel_id')
            ? app('current_channel_id')
            : auth('user')->user()->channel_id;

        return parent::create($data);
    }
}
