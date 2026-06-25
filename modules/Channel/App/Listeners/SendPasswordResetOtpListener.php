<?php

namespace Modules\Channel\App\Listeners;

use Modules\Channel\App\Events\PasswordResetRequested;
use Modules\Channel\App\Jobs\SendPasswordResetOtpJob;
use Modules\Core\App\Repositories\OtpRepository;

class SendPasswordResetOtpListener
{
    public function __construct(protected OtpRepository $otpRepository) {}

    public function handle(PasswordResetRequested $event): void
    {
        $this->otpRepository->invalidatePrevious($event->user, 'password_reset');

        $otp = $this->otpRepository->generate($event->user, 'password_reset', 15);

        SendPasswordResetOtpJob::dispatch($event->user, $otp->code);
    }
}
