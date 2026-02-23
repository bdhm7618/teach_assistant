<?php

namespace Modules\Channel\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class UniqueInChannel implements ValidationRule
{
    protected string $modelClass;
    protected array $columns;
    protected ?int $channelId;
    protected $ignoreId;

    /**
     * Create a new rule instance.
     *
     * @param string $modelClass The model class to check
     * @param array $columns The columns to check for uniqueness
     * @param int|null $channelId Optional channel ID, if null will get from authenticated user
     * @param mixed $ignoreId Optional ID to ignore (useful for update operations)
     */
    public function __construct(
        string $modelClass,
        array $columns,
        ?int $channelId = null,
        $ignoreId = null
    ) {
        $this->modelClass = $modelClass;
        $this->columns = $columns;
        $this->channelId = $channelId ?? $this->getChannelId();
        $this->ignoreId = $ignoreId;
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

        // Check if model uses HasChannelScope trait
        $usesChannelScope = in_array(
            \Modules\Channel\App\Traits\HasChannelScope::class,
            class_uses_recursive($this->modelClass)
        );

        $query = $usesChannelScope
            ? $this->modelClass::withoutChannelScope()
            : $this->modelClass::query();

        $query->where('channel_id', $this->channelId);

        // Add conditions for each column
        foreach ($this->columns as $column) {
            // Get the value from request
            $value = request()->input($column);
            if ($value !== null) {
                $query->where($column, $value);
            }
        }

        // Exclude current record when updating
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail(trans('channel::app.validation.unique_in_channel'), null);
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

