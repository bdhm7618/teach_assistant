<?php

namespace Modules\Assignment\App\Repositories;

use Modules\Assignment\App\Models\Assignment;
use Prettus\Repository\Eloquent\BaseRepository;

class AssignmentRepository extends BaseRepository
{
    public function model(): string
    {
        return Assignment::class;
    }

    public function create(array $data): Assignment
    {
        $data['channel_id'] = app()->has('current_channel_id')
            ? app('current_channel_id')
            : auth('user')->user()->channel_id;

        return parent::create($data);
    }
}
