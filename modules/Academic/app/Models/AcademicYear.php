<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasChannelScope;
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
