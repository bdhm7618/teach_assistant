<?php

namespace Modules\Academic\Repositories;

use Modules\Academic\App\Models\SessionTime;
use Prettus\Repository\Eloquent\BaseRepository;

class SessionTimeRepository extends BaseRepository
{
    public function model()
    {
        return SessionTime::class ; 
    }
}
