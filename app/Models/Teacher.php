<?php

namespace App\Models;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'status',
        'image',
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
}
