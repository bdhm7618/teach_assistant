<?php

namespace Modules\Channel\App\Models;

use Modules\Core\App\Models\Otp;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends  Authenticatable implements JWTSubject
{
    use HasFactory;

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
}
