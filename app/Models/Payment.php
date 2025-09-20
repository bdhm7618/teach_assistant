<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'teacher_id',
        'payment_month_id',
        'amount',
        'discount',
        'currency',
        'status',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    // Relations
    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Teacher::class);
    }

    public function paymentMonth(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentMonth::class);
    }

    // ensure payment_month is open on create
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            // If no paid_at provided, set now
            if (!$payment->paid_at) {
                $payment->paid_at = now();
            }

            // Ensure payment_month exists & is open
            if (! $payment->payment_month_id) {
                throw new \InvalidArgumentException('payment_month_id is required.');
            }

            $open = \App\Models\PaymentMonth::isOpen($payment->payment_month_id);
            if (! $open) {
                throw new \Exception("Payments for this month (id: {$payment->payment_month_id}) are closed.");
            }
        });
    }
}
