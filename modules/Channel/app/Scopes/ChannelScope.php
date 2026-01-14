<?php

namespace Modules\Channel\App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ChannelScope implements Scope
{
    /**
     * Models that should be excluded from channel scope
     * (e.g., Admin models that are not tenant-specific, Channel model itself, User model to avoid infinite loop)
     */
    protected $excludedModels = [
        \Modules\Admin\Models\Admin::class,
        \Modules\Channel\App\Models\Channel::class,
        \Modules\Channel\App\Models\User::class,
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // Skip scope for excluded models (like Admin)
        if ($this->isExcluded($model)) {
            return;
        }

        // Get channel_id from authenticated user
        $channelId = $this->getChannelId();
        
        if ($channelId !== null) {
            // For subjects, include both channel-specific and general subjects (channel_id = null)
            if ($model instanceof \Modules\Academic\App\Models\Subject) {
                $builder->where(function ($query) use ($channelId) {
                    $query->where('channel_id', $channelId)
                          ->orWhereNull('channel_id');
                });
            } else {
                $builder->where('channel_id', $channelId);
            }
        }
    }

    /**
     * Check if the model should be excluded from channel scope
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function isExcluded(Model $model)
    {
        foreach ($this->excludedModels as $excludedModel) {
            if ($model instanceof $excludedModel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the channel_id from the authenticated user
     * Only applies when user is authenticated via 'user' guard (not 'admin')
     *
     * @return int|null
     */
    protected function getChannelId()
    {
        // Only apply scope if user is authenticated via 'user' guard
        // Admin guard should not have channel scope applied
        if (auth("user")->check()) {
            return auth('user')->user()?->channel_id;
        }

        return null;
    }
}

