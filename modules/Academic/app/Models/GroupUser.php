<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    use HasChannelScope;

    protected $table = 'group_users';

    protected $fillable = [
        'channel_id',
        'group_id',
        'user_id',
        'role_type',
        'status',
        'joined_at',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Get the group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\User::class);
    }

    /**
     * Get the channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Check if user is teacher
     */
    public function isTeacher(): bool
    {
        return $this->role_type === 'teacher';
    }

    /**
     * Check if user is assistant
     */
    public function isAssistant(): bool
    {
        return $this->role_type === 'assistant';
    }

    /**
     * Check if membership is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

