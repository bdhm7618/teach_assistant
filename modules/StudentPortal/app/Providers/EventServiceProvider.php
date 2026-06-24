<?php

namespace Modules\StudentPortal\App\Providers;

use Modules\StudentPortal\App\Events\StudentRegistered;
use Modules\StudentPortal\App\Events\StudentPasswordResetRequested;
use Modules\StudentPortal\App\Listeners\SendStudentEmailVerificationListener;
use Modules\StudentPortal\App\Listeners\SendStudentPasswordResetOtpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StudentRegistered::class => [
            SendStudentEmailVerificationListener::class,
        ],
        StudentPasswordResetRequested::class => [
            SendStudentPasswordResetOtpListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
