<?php

namespace Modules\ParentPortal\App\Providers;

use Modules\ParentPortal\App\Events\ParentRegistered;
use Modules\ParentPortal\App\Events\ParentPasswordResetRequested;
use Modules\ParentPortal\App\Listeners\SendParentEmailVerificationListener;
use Modules\ParentPortal\App\Listeners\SendParentPasswordResetOtpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ParentRegistered::class => [
            SendParentEmailVerificationListener::class,
        ],
        ParentPasswordResetRequested::class => [
            SendParentPasswordResetOtpListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
