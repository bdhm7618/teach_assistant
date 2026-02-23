<?php

namespace Modules\Payment\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'invoice_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'notes',
        'channel_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Get the invoice this installment belongs to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get all payments for this installment
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the channel that owns this installment
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Check if installment is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if installment is overdue
     */
    public function isOverdue(): bool
    {
        return !$this->isPaid() && $this->due_date < now()->toDateString();
    }

    /**
     * Get paid amount for this installment
     */
    public function getPaidAmount(): float
    {
        return $this->payments()
            ->where('status', \Modules\Payment\App\Enums\PaymentStatus::COMPLETED)
            ->sum('final_amount');
    }
}

