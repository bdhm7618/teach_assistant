<?php

namespace Modules\Channel\App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Channel\App\Rules\BelongsToChannel;
use Modules\Channel\App\Rules\UniqueInChannel;

abstract class BaseRequest extends FormRequest
{
    /**
     * Get the current channel ID from authenticated user
     *
     * @return int|null
     */
    protected function getChannelId(): ?int
    {
        if (auth("user")->check()) {
            return auth('user')->user()?->channel_id;
        }

        return null;
    }

    /**
     * Validate that a model ID belongs to the current channel
     *
     * @param string $modelClass The model class to check
     * @param int|null $channelId Optional channel ID
     * @return BelongsToChannel
     */
    protected function belongsToChannel(string $modelClass, ?int $channelId = null): BelongsToChannel
    {
        return new BelongsToChannel($modelClass, $channelId ?? $this->getChannelId());
    }

    /**
     * Validate uniqueness within the current channel
     *
     * @param string $modelClass The model class to check
     * @param array $columns The columns to check for uniqueness
     * @param mixed $ignoreId Optional ID to ignore (useful for update operations)
     * @return UniqueInChannel
     */
    protected function uniqueInChannel(string $modelClass, array $columns, $ignoreId = null): UniqueInChannel
    {
        return new UniqueInChannel(
            $modelClass,
            $columns,
            $this->getChannelId(),
            $ignoreId
        );
    }
}

