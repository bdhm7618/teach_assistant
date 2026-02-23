<?php

namespace Modules\Payment\App\Enums;

enum PaymentPeriodType: string
{
    case MONTHLY = 'monthly';
    case WEEKLY = 'weekly';
    case DAILY = 'daily';
    case SESSION = 'session';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => trans('payment::app.period.monthly'),
            self::WEEKLY => trans('payment::app.period.weekly'),
            self::DAILY => trans('payment::app.period.daily'),
            self::SESSION => trans('payment::app.period.session'),
            self::CUSTOM => trans('payment::app.period.custom'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MONTHLY => trans('payment::app.period.monthly_desc'),
            self::WEEKLY => trans('payment::app.period.weekly_desc'),
            self::DAILY => trans('payment::app.period.daily_desc'),
            self::SESSION => trans('payment::app.period.session_desc'),
            self::CUSTOM => trans('payment::app.period.custom_desc'),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

