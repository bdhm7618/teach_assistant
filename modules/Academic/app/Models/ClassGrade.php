<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class ClassGrade extends Model
{
    use HasChannelScope;
    protected $table = 'classes';

    protected $fillable = [
        'grade_level',
        'stage',
        'is_active',
        'channel_id'
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute()
    {
        $map = [
            'primary' => ['أولى', 'ثانية', 'ثالثة', 'رابعة', 'خامسة', 'سادسة'],
            'preparatory' => ['أولى', 'ثانية', 'ثالثة'],
            'secondary' => ['أولى', 'ثانية', 'ثالثة'],
        ];

        return $map[$this->stage][$this->grade_level - 1] . ' ' . match ($this->stage) {
            'primary' => 'ابتدائي',
            'preparatory' => 'إعدادي',
            'secondary' => 'ثانوي',
        };
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
