<?php

namespace Modules\Notification\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Assignment\App\Events\AssignmentPublished;
use Modules\Notification\App\Notifications\AssignmentPublishedNotification;
use Modules\Notification\App\Services\NotificationService;

class SendAssignmentPublishedNotification implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment;
        $group      = $assignment->group()->with('students')->first();
        $channel    = app()->has('current_channel') ? app('current_channel') : null;

        if (!$group) {
            return;
        }

        foreach ($group->students as $student) {
            if (!$student->email) {
                continue;
            }

            $this->notificationService->send(
                $student,
                new AssignmentPublishedNotification(
                    studentName:         $student->name,
                    channelName:         $channel?->name ?? 'Your Center',
                    assignmentTitle:     $assignment->title,
                    groupName:           $group->name,
                    dueAt:               $assignment->due_at?->toDateTimeString(),
                    allowLateSubmission: $assignment->allow_late_submission,
                ),
                $assignment->channel_id,
                'assignment_published'
            );
        }
    }
}
