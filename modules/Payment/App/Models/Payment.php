<?php

namespace Modules\Payment\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Modules\Payment\App\Enums\PaymentStatus;
use Modules\Payment\App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'student_id',
        'group_id',
        'payment_period_id',
        'invoice_id',
        'installment_id',
        'amount',
        'discount_amount',
        'final_amount',
        'payment_date',
        'payment_method',
        'status',
        'reference_number',
        'transaction_id',
        'notes',
        'paid_by',
        'channel_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    /**
     * Get the student that made this payment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    /**
     * Get the group this payment is for (private lesson)
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Group::class);
    }

    /**
     * Get the payment period this payment belongs to
     */
    public function paymentPeriod(): BelongsTo
    {
        return $this->belongsTo(PaymentPeriod::class);
    }

    /**
     * Get the invoice this payment belongs to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the installment this payment belongs to
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * Get the channel that owns this payment
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Get the user who recorded this payment
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\User::class, 'paid_by');
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope for filtering by payment method
     */
    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('payment_method', $method->value);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED->value);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING->value);
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }

    /**
     * Calculate final amount after discount
     */
    public function calculateFinalAmount(): float
    {
        return $this->amount - ($this->discount_amount ?? 0);
    }
}

