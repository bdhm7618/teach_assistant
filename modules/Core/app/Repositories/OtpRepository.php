<?php

namespace Modules\Core\App\Repositories;

use Modules\Core\App\Models\Otp;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Eloquent\BaseRepository;

class OtpRepository extends BaseRepository
{
    public function model()
    {
        return Otp::class;
    }


    public function generate(
        Model $otpable,
        string $type = 'email_verification',
        int $expiresInMinutes = 60
    ): Otp {
        return $otpable->otps()->create([
            'type'       => $type,
            'code'       => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
    }

    public function getLatestUnverified(
        Model $otpable,
        string $type
    ): ?Otp {
        return Otp::where('otpable_id', $otpable->id)
            ->where('otpable_type', get_class($otpable))
            ->where('type', $type)
            ->whereNull('verified_at')
            ->latest()
            ->first();
    }

    public function invalidatePrevious(
        Model $otpable,
        string $type
    ): void {
        Otp::where('otpable_id', $otpable->id)
            ->where('otpable_type', get_class($otpable))
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();
    }

    public function markAsVerified(Otp $otp): void
    {
        $otp->update([
            'verified_at' => now(),
        ]);
    }   

    public function deleteExpired(): int
    {
        return Otp::where('expires_at', '<', now())->delete();
    }
}
