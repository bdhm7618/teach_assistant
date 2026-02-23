<?php

namespace Modules\Channel\App\Listeners;


use Modules\Channel\App\Events\UserRegistered;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\Channel\App\Jobs\SendEmailVerificationJob;

class SendEmailVerificationListener
{
    public function __construct(
        protected OtpRepository $otpRepository
    ) {}

    public function handle(UserRegistered $event): void
    {
        $otp = $this->otpRepository->generate(
            $event->user,
            24 * 60
        );

        SendEmailVerificationJob::dispatch(
            $event->user,
            $otp->code
        );
    }
}
