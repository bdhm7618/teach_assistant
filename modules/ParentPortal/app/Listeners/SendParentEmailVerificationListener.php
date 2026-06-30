<?php

namespace Modules\ParentPortal\App\Listeners;

use Modules\ParentPortal\App\Events\ParentRegistered;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\Channel\App\Jobs\SendEmailVerificationJob;

class SendParentEmailVerificationListener
{
    public function __construct(protected OtpRepository $otpRepository) {}

    public function handle(ParentRegistered $event): void
    {
        $this->otpRepository->invalidatePrevious($event->parent, 'email_verification');
        $otp = $this->otpRepository->generate($event->parent, 'email_verification', 24 * 60);
        SendEmailVerificationJob::dispatch($event->parent, $otp->code);
    }
}
