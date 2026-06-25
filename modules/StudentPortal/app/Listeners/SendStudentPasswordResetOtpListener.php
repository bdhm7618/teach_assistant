<?php

namespace Modules\StudentPortal\App\Listeners;

use Modules\StudentPortal\App\Events\StudentPasswordResetRequested;
use Modules\Core\App\Repositories\OtpRepository;
use Modules\Channel\App\Jobs\SendPasswordResetOtpJob;

class SendStudentPasswordResetOtpListener
{
    public function __construct(protected OtpRepository $otpRepository) {}

    public function handle(StudentPasswordResetRequested $event): void
    {
        $this->otpRepository->invalidatePrevious($event->student, 'password_reset');
        $otp = $this->otpRepository->generate($event->student, 'password_reset', 60);
        SendPasswordResetOtpJob::dispatch($event->student, $otp->code);
    }
}
