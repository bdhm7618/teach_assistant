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

    /**
     * Get the student enrollments for this group
     */
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get active enrollments for this group
     */
    public function activeEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class)->where('status', 'active');
    }

    /**
     * Get the users (teachers, assistants, helpers) in this group
     */
    public function users()
    {
        return $this->belongsToMany(
            \Modules\Channel\App\Models\User::class,
            'group_users',
            'group_id',
            'user_id'
        )->withPivot(['role_type', 'status', 'joined_at', 'notes'])
          ->withTimestamps()
          ->using(GroupUser::class);
    }

    /**
     * Get teachers in this group
     */
    public function teachers()
    {
        return $this->users()->wherePivot('role_type', 'teacher')->wherePivot('status', 'active');
    }

    /**
     * Get assistants in this group
     */
    public function assistants()
    {
        return $this->users()->wherePivot('role_type', 'assistant')->wherePivot('status', 'active');
    }

    /**
     * Get helpers in this group
     */
    public function helpers()
    {
        return $this->users()->wherePivot('role_type', 'helper')->wherePivot('status', 'active');
    }

    /**
     * Get group users relationship (direct)
     */
    public function groupUsers()
    {
        return $this->hasMany(GroupUser::class);
    }
}
