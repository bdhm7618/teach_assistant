<?php

namespace Modules\Student\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Student extends Authenticatable
{
    use HasChannelScope, Notifiable;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'status',
        'channel_id',
        'image',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * Set password attribute with hashing
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Get the groups that this student belongs to
     */
    public function groups()
    {
        return $this->belongsToMany(
            \Modules\Academic\App\Models\Group::class,
            'group_students',
            'student_id',
            'group_id'
        )->withTimestamps();
    }

    /**
     * Get the channel that owns the student
     */
    public function channel()
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    // /**
    //  * Get attendance records for this student
    //  */
    // public function attendances()
    // {
    //     return $this->hasMany(\Modules\Channel\App\Models\Attendance::class);
    // }

    // /**
    //  * Get payments for this student
    //  */
    // public function payments()
    // {
    //     return $this->hasMany(\Modules\Channel\App\Models\Payment::class);
    // }

    /**
     * Check if student is active
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Scope for active students
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for inactive students
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }
}

