<?php

namespace Modules\Payment\App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => trans('payment::app.status.pending'),
            self::COMPLETED => trans('payment::app.status.completed'),
            self::FAILED => trans('payment::app.status.failed'),
            self::REFUNDED => trans('payment::app.status.refunded'),
            self::CANCELLED => trans('payment::app.status.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'info',
            self::CANCELLED => 'secondary',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeRefunded(): bool
    {
        return $this === self::COMPLETED;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

