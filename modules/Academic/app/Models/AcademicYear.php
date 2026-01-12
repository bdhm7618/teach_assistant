<?php

namespace Modules\Academic\App\Models;


use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'start_year',
        'end_year',
        'is_active',
        'channel_id'
    ];

    public function classes()
    {
        return $this->hasMany(ClassGrade::class);
    }
}
