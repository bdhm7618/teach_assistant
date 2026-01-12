<?php

namespace Modules\Academic\App\Models;


use Illuminate\Database\Eloquent\Model;

class SessionTime extends Model
{
    protected $fillable = [
        'session_time',
        'day_name',
        'group_id',
        'is_active'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
