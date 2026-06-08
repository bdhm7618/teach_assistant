<?php

namespace Modules\Channel\App\Traits;

use Modules\Channel\App\Scopes\ChannelScope;

trait HasChannelScope
{
    /**
     * Boot the trait and apply the channel scope
     *
     * @return void
     */
    protected static function bootHasChannelScope()
    {
        static::addGlobalScope(new ChannelScope());

        static::creating(function ($model) {
            if (empty($model->channel_id)) {
                if (app()->has('current_channel_id')) {
                    $model->channel_id = app('current_channel_id');
                } elseif (auth('user')->check()) {
                    $model->channel_id = auth('user')->user()?->channel_id;
                }
            }
        });
    }

    /**
     * Get all models without the channel scope
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withoutChannelScope()
    {
        return static::withoutGlobalScope(ChannelScope::class);
    }
}
