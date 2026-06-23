<?php

namespace Modules\Exam\App\Repositories;

use Modules\Exam\App\Models\Exam;
use Prettus\Repository\Eloquent\BaseRepository;

class ExamRepository extends BaseRepository
{
    public function model(): string
    {
        return Exam::class;
    }

    public function create(array $data): Exam
    {
        $data['channel_id'] = app()->has('current_channel_id')
            ? app('current_channel_id')
            : auth('user')->user()->channel_id;

        return parent::create($data);
    }
}
