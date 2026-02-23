<?php

namespace Modules\Payment\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Modules\Payment\App\Enums\PaymentPeriodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PaymentPeriod extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'name',
        'period_type',
        'start_date',
        'end_date',
        'month',
        'year',
        'is_open',
        'is_active',
        'notes',
        'channel_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'period_type' => PaymentPeriodType::class,
        'is_open' => 'boolean',
        'is_active' => 'boolean',
        'month' => 'integer',
        'year' => 'integer',
    ];

    /**
     * Get the channel that owns this payment period
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Channel\App\Models\Channel::class);
    }

    /**
     * Get all payments for this period
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate period name based on type
     */
    public static function generateName(PaymentPeriodType $type, ?int $month = null, ?int $year = null, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;

        return match ($type) {
            PaymentPeriodType::MONTHLY => trans('payment::app.period.month_name', [
                'month' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                'year' => $year
            ]),
            PaymentPeriodType::WEEKLY => trans('payment::app.period.week_name', [
                'start' => $startDate?->format('Y-m-d') ?? '',
                'end' => $endDate?->format('Y-m-d') ?? ''
            ]),
            PaymentPeriodType::DAILY => $startDate?->format('Y-m-d') ?? Carbon::today()->format('Y-m-d'),
            PaymentPeriodType::SESSION => trans('payment::app.period.session_period'),
            PaymentPeriodType::CUSTOM => trans('payment::app.period.custom_period'),
        };
    }

    /**
     * Create monthly period
     */
    public static function createMonthly(int $year, int $month, ?int $channelId = null): self
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return self::create([
            'name' => self::generateName(PaymentPeriodType::MONTHLY, $month, $year),
            'period_type' => PaymentPeriodType::MONTHLY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'month' => $month,
            'year' => $year,
            'is_open' => true,
            'is_active' => true,
            'channel_id' => $channelId ?? auth('user')->user()?->channel_id,
        ]);
    }

    /**
     * Create weekly period
     */
    public static function createWeekly(Carbon $startDate, Carbon $endDate, ?int $channelId = null): self
    {
        return self::create([
            'name' => self::generateName(PaymentPeriodType::WEEKLY, null, null, $startDate, $endDate),
            'period_type' => PaymentPeriodType::WEEKLY,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'month' => $startDate->month,
            'year' => $startDate->year,
            'is_open' => true,
            'is_active' => true,
            'channel_id' => $channelId ?? auth('user')->user()?->channel_id,
        ]);
    }

    /**
     * Create session-based period
     */
    public static function createSession(string $name, ?Carbon $startDate = null, ?Carbon $endDate = null, ?int $channelId = null): self
    {
        return self::create([
            'name' => $name,
            'period_type' => PaymentPeriodType::SESSION,
            'start_date' => $startDate ?? Carbon::today(),
            'end_date' => $endDate ?? Carbon::today(),
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'is_open' => true,
            'is_active' => true,
            'channel_id' => $channelId ?? auth('user')->user()?->channel_id,
        ]);
    }

    /**
     * Check if period is open for payments
     */
    public function isOpen(): bool
    {
        return $this->is_open && $this->is_active;
    }

    /**
     * Check if date falls within period
     */
    public function containsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Get total payments amount for this period
     */
    public function getTotalPayments(): float
    {
        return $this->payments()
            ->where('status', \Modules\Payment\App\Enums\PaymentStatus::COMPLETED)
            ->sum('final_amount');
    }

    /**
     * Get payments count for this period
     */
    public function getPaymentsCount(): int
    {
        return $this->payments()->count();
    }
}

