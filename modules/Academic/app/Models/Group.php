<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasChannelScope;
    protected $fillable = [
        'name',
        'code',
        'class_id',
        'subject_id',
        'academic_year_id',
        'number_of_sessions',
        'price',
        'is_active',
        'channel_id'
    ];

    public function sessions()
    {
        return $this->hasMany(SessionTime::class);
    }

   
}
