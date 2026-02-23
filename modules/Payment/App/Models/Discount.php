<?php

namespace Modules\Payment\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Discount extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'is_active',
        'applies_to',
        'channel_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the channel that owns this discount
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        $discount = match ($this->type) {
            'percentage' => ($amount * $this->value) / 100,
            'fixed' => $this->value,
            default => 0,
        };

        // Apply max discount limit if set
        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return min($discount, $amount); // Can't discount more than the amount
    }

    /**
     * Check if discount is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}

