<?php

namespace Modules\Channel\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Channel\App\Traits\HasChannelScope;

class Role extends Model
{
    use HasFactory, HasChannelScope;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'permissions',
        'channel_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the channel that owns this role.
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get all users with this role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this is a general role (available to all channels).
     *
     * @return bool
     */
    public function isGeneral(): bool
    {
        return $this->channel_id === null;
    }

    /**
     * Check if this is a channel-specific role.
     *
     * @return bool
     */
    public function isChannelSpecific(): bool
    {
        return $this->channel_id !== null;
    }

    /**
     * Check if role has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->permissions === 'all' || $this->permissions === ['all']) {
            return true;
        }

        if (is_array($this->permissions)) {
            return in_array($permission, $this->permissions);
        }

        return false;
    }

    /**
     * Check if role has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if role has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this is a system role.
     *
     * @return bool
     */
    public function isSystemRole(): bool
    {
        return in_array($this->name, ['owner', 'teacher', 'assistant', 'viewer']);
    }
}
