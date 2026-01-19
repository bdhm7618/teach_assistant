<?php

namespace Modules\Payment\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'group_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'issue_date',
        'status',
        'notes',
        'channel_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'issue_date' => 'date',
    ];

    /**
     * Get the student this invoice belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Student\App\Models\Student::class);
    }

    /**
     * Get the group this invoice is for
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academic\App\Models\Group::class);
    }

    /**
     * Get all payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all installments for this invoice
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * Get the channel that owns this invoice
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(int $channelId): string
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $count = self::where('channel_id', $channelId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('INV-%s-%s-%04d', $channelId, $year . $month, $count);
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return !$this->isFullyPaid() && $this->due_date < Carbon::today();
    }

    /**
     * Update paid and remaining amounts
     */
    public function updatePaymentStatus(): void
    {
        $this->paid_amount = $this->payments()
            ->where('status', \Modules\Payment\App\Enums\PaymentStatus::COMPLETED)
            ->sum('final_amount');
        
        $this->remaining_amount = $this->final_amount - $this->paid_amount;
        $this->status = $this->isFullyPaid() ? 'paid' : ($this->isOverdue() ? 'overdue' : 'pending');
        $this->save();
    }
}

