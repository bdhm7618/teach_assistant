<?php

namespace Modules\Notification\App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\App\Listeners\SendEnrollmentNotification;
use Modules\Notification\App\Listeners\SendExamPublishedNotification;
use Modules\Notification\App\Listeners\SendAssignmentPublishedNotification;
use Modules\Notification\App\Listeners\SendInvoiceCreatedNotification;
use Modules\Notification\App\Listeners\SendSubmissionGradedNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Academic\App\Events\StudentEnrolled::class => [
            SendEnrollmentNotification::class,
        ],
        \Modules\Exam\App\Events\ExamPublished::class => [
            SendExamPublishedNotification::class,
        ],
        \Modules\Assignment\App\Events\AssignmentPublished::class => [
            SendAssignmentPublishedNotification::class,
        ],
        \Modules\Payment\App\Events\InvoiceCreated::class => [
            SendInvoiceCreatedNotification::class,
        ],
        \Modules\Exam\App\Events\SubmissionGraded::class => [
            SendSubmissionGradedNotification::class,
        ],
        \Modules\Assignment\App\Events\SubmissionGraded::class => [
            SendSubmissionGradedNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
