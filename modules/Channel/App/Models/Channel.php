<?php

namespace Modules\Channel\App\Models;

use Modules\Admin\App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Channel\App\Models\User;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'status',
        'trial_ends_at',
        'type',
        'is_private',
        'created_by_admin',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'is_private'    => 'boolean',
    ];

    public function isAccessible(): bool
    {
        return $this->status === 'active';
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

