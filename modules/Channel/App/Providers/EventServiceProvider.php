<?php

namespace Modules\Channel\App\Providers;


use Modules\Channel\App\Events\UserRegistered;
use Modules\Channel\App\Events\PasswordResetRequested;
use Modules\Channel\App\Listeners\SendEmailVerificationListener;
use Modules\Channel\App\Listeners\SendPasswordResetOtpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        UserRegistered::class => [
            SendEmailVerificationListener::class,
        ],
        PasswordResetRequested::class => [
            SendPasswordResetOtpListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
