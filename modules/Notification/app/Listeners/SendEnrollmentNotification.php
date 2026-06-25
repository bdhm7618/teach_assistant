<?php

namespace Modules\Notification\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Academic\App\Events\StudentEnrolled;
use Modules\Notification\App\Notifications\EnrollmentConfirmedNotification;
use Modules\Notification\App\Services\NotificationService;

class SendEnrollmentNotification implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(StudentEnrolled $event): void
    {
        $enrollment = $event->enrollment;
        $student    = $enrollment->student;

        if (!$student || !$student->email) {
            return;
        }

        $group   = $enrollment->group()->with('course')->first();
        $channel = app()->has('current_channel') ? app('current_channel') : $group?->channel;

        $this->notificationService->send(
            $student,
            new EnrollmentConfirmedNotification(
                studentName:        $student->name,
                groupName:          $group?->name ?? '',
                courseName:         $group?->course?->name ?? '',
                channelName:        $channel?->name ?? 'Your Center',
                firstInvoiceAmount: $event->firstInvoiceAmount,
            ),
            $enrollment->channel_id,
            'enrollment_confirmed'
        );
    }
}
