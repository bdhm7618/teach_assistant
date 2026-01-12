<?php

namespace Modules\Academic\App\Models;


use Illuminate\Database\Eloquent\Model;


class Group extends Model
{
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
