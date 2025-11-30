<?php

namespace Modules\Channel\Models;

use Modules\Channel\Models\Channel;
use Tymon\JWTAuth\Contracts\JWTSubject;
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
        'channel_id',
        "is_admin"
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
