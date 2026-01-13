<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class ClassGrade extends Model
{
    use HasChannelScope;
    protected $table = 'class_grades';

    protected $fillable = [
        'grade_level',
        'stage',
        'is_active',
        'channel_id',
        "academic_year_id"
    ];


    public function groups()
    {
        return $this->hasMany(Group::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
