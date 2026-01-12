<?php

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Otp extends Model
{
    protected $fillable = [
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function otpable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function markVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
