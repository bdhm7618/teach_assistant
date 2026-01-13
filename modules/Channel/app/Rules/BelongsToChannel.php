<?php

namespace Modules\Channel\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class BelongsToChannel implements ValidationRule
{
    protected string $modelClass;
    protected ?int $channelId;

    /**
     * Create a new rule instance.
     *
     * @param string $modelClass The model class to check
     * @param int|null $channelId Optional channel ID, if null will get from authenticated user
     */
    public function __construct(string $modelClass, ?int $channelId = null)
    {
        $this->modelClass = $modelClass;
        $this->channelId = $channelId ?? $this->getChannelId();
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->channelId) {
            return; // Skip validation if no channel ID
        }

        if (!$value) {
            return; // Skip if value is empty (let required rule handle it)
        }

        // Check if model uses HasChannelScope trait
        $usesChannelScope = in_array(
            \Modules\Channel\App\Traits\HasChannelScope::class,
            class_uses_recursive($this->modelClass)
        );

        if ($usesChannelScope) {
            // Use withoutChannelScope to bypass global scope
            $model = $this->modelClass::withoutChannelScope()
                ->where('id', $value)
                ->where('channel_id', $this->channelId)
                ->first();
        } else {
            // Model doesn't use channel scope, check directly
            $model = $this->modelClass::where('id', $value)
                ->where('channel_id', $this->channelId)
                ->first();
        }

        if (!$model) {
            $modelName = class_basename($this->modelClass);
            $fail(trans('channel::app.validation.model_not_belongs_to_channel'), null);
        }
    }

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
}
