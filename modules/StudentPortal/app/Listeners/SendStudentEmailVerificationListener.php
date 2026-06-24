<?php

namespace Modules\StudentPortal\App\Listeners;

use Modules\StudentPortal\App\Events\StudentRegistered;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\Channel\App\Jobs\SendEmailVerificationJob;

class SendStudentEmailVerificationListener
{
    public function __construct(protected OtpRepository $otpRepository) {}

    public function handle(StudentRegistered $event): void
    {
        $this->otpRepository->invalidatePrevious($event->student, 'email_verification');
        $otp = $this->otpRepository->generate($event->student, 'email_verification', 24 * 60);
        SendEmailVerificationJob::dispatch($event->student, $otp->code);
    }
}
