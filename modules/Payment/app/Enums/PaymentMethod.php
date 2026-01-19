<?php

namespace Modules\Payment\App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case VODAFONE_CASH = 'vodafone_cash';
    case ORANGE_MONEY = 'orange_money';
    case ETISALAT_CASH = 'etisalat_cash';
    case EASY_PAY = 'easy_pay';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case ONLINE = 'online';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => trans('payment::app.method.cash'),
            self::BANK_TRANSFER => trans('payment::app.method.bank_transfer'),
            self::VODAFONE_CASH => trans('payment::app.method.vodafone_cash'),
            self::ORANGE_MONEY => trans('payment::app.method.orange_money'),
            self::ETISALAT_CASH => trans('payment::app.method.etisalat_cash'),
            self::EASY_PAY => trans('payment::app.method.easy_pay'),
            self::CREDIT_CARD => trans('payment::app.method.credit_card'),
            self::DEBIT_CARD => trans('payment::app.method.debit_card'),
            self::ONLINE => trans('payment::app.method.online'),
            self::OTHER => trans('payment::app.method.other'),
        };
    }

    public function isMobileWallet(): bool
    {
        return in_array($this, [
            self::VODAFONE_CASH,
            self::ORANGE_MONEY,
            self::ETISALAT_CASH,
            self::EASY_PAY,
        ]);
    }

    public function isCard(): bool
    {
        return in_array($this, [
            self::CREDIT_CARD,
            self::DEBIT_CARD,
        ]);
    }

    public function requiresReference(): bool
    {
        return in_array($this, [
            self::BANK_TRANSFER,
            self::VODAFONE_CASH,
            self::ORANGE_MONEY,
            self::ETISALAT_CASH,
            self::EASY_PAY,
            self::CREDIT_CARD,
            self::DEBIT_CARD,
            self::ONLINE,
        ]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

