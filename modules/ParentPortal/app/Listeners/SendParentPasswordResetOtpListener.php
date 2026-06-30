<?php

namespace Modules\ParentPortal\App\Listeners;

use Modules\ParentPortal\App\Events\ParentPasswordResetRequested;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\Channel\App\Jobs\SendPasswordResetOtpJob;

class SendParentPasswordResetOtpListener
{
    public function __construct(protected OtpRepository $otpRepository) {}

    public function handle(ParentPasswordResetRequested $event): void
    {
        $this->otpRepository->invalidatePrevious($event->parent, 'password_reset');
        $otp = $this->otpRepository->generate($event->parent, 'password_reset', 60);
        SendPasswordResetOtpJob::dispatch($event->parent, $otp->code);
    }
}
