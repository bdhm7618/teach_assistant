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

