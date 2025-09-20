<?php

namespace App\Models;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

class PaymentMonth extends Model
{
    protected $fillable = ['month', 'is_open' , "month_date" , "status"];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function isOpen(int $id): bool
    {
        return self::where('id', $id)->where('is_open', true)->exists();
    }
}
