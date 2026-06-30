<?php

namespace Modules\ParentPortal\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class ParentAccount extends Authenticatable implements JWTSubject
{
    use HasChannelScope, Notifiable;

    protected $table = 'parents';

    protected $fillable = [
        'channel_id',
        'name',
        'email',
        'phone',
        'password',
        'image',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status'            => 'integer',
    ];

    /**
     * Hash the password on assignment.
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * The students (children) linked to this parent.
     */
    public function students()
    {
        return $this->belongsToMany(
            \Modules\Student\App\Models\Student::class,
            'parent_student',
            'parent_id',
            'student_id'
        )->withPivot(['relationship', 'is_primary'])->withTimestamps();
    }

    public function channel()
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Polymorphic OTP records (email verification / password reset).
     */
    public function otps()
    {
        return $this->morphMany(\Modules\Core\App\Models\Otp::class, 'otpable');
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Assert that the given student id belongs to this parent.
     */
    public function ownsStudent($studentId): bool
    {
        return $this->students()->where('students.id', $studentId)->exists();
    }

    // ---- JWTSubject ----

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
