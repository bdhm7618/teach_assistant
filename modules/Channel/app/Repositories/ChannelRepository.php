<?php

namespace Modules\Channel\Repositories;

use Modules\Channel\Models\Channel;
use Prettus\Repository\Eloquent\BaseRepository;

class ChannelRepository extends BaseRepository
{
   public function model()
   {
    return Channel::class ; 
   }
}
