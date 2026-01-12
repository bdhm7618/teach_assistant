<?php

namespace Modules\Academic\App\Repositories;


use Modules\Academic\App\Models\Group;
use Prettus\Repository\Eloquent\BaseRepository;

class GroupRepository extends BaseRepository
{
    public function model()
    {
        return Group::class;
    }
}
