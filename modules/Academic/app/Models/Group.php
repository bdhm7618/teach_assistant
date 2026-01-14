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
        'class_grade_id',
        'subject_id',
        'capacity',
        'price',
        'is_active',
        'channel_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the class grade that owns the group
     */
    public function classGrade()
    {
        return $this->belongsTo(ClassGrade::class, 'class_grade_id');
    }

    /**
     * Get the subject for this group
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the session times for this group
     */
    public function sessions()
    {
        return $this->hasMany(SessionTime::class);
    }

    /**
     * Get the students in this group
     */
    public function students()
    {
        return $this->belongsToMany(\Modules\Student\App\Models\Student::class, 'group_students', 'group_id', 'student_id')
            ->withTimestamps();
    }
}
