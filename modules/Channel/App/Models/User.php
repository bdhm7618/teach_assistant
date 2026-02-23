<?php

namespace Modules\Channel\App\Models;

use Modules\Core\App\Models\Otp;
use Modules\Channel\App\Traits\HasChannelScope;
use Modules\Channel\App\Traits\HasPermissions;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasChannelScope, HasPermissions;

    public function getJWTIdentifier()  
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }



    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'status',
        'image',
        'role_id',
        'channel_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, "role_id");
    }


    public function otps(): MorphMany
    {
        return $this->morphMany(Otp::class, 'otpable');
    }

    /**
     * Get the groups this user is assigned to (as teacher, assistant, etc.)
     */
    public function groups()
    {
        return $this->belongsToMany(
            \Modules\Academic\App\Models\Group::class,
            'group_users',
            'user_id',
            'group_id'
        )->withPivot(['role_type', 'status', 'joined_at', 'notes'])
          ->withTimestamps()
          ->using(\Modules\Academic\App\Models\GroupUser::class);
    }

    /**
     * Get groups where user is a teacher
     */
    public function teachingGroups()
    {
        return $this->groups()->wherePivot('role_type', 'teacher')->wherePivot('status', 'active');
    }

    /**
     * Get groups where user is an assistant
     */
    public function assistingGroups()
    {
        return $this->groups()->wherePivot('role_type', 'assistant')->wherePivot('status', 'active');
    }

    /**
     * Get group users relationship (direct)
     */
    public function groupUsers()
    {
        return $this->hasMany(\Modules\Academic\App\Models\GroupUser::class);
    }
}
