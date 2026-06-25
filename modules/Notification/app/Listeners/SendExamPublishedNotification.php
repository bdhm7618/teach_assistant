<?php

namespace Modules\Notification\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Exam\App\Events\ExamPublished;
use Modules\Notification\App\Notifications\ExamPublishedNotification;
use Modules\Notification\App\Services\NotificationService;

class SendExamPublishedNotification implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(ExamPublished $event): void
    {
        $exam    = $event->exam;
        $group   = $exam->group()->with('students')->first();
        $channel = app()->has('current_channel') ? app('current_channel') : null;

        if (!$group) {
            return;
        }

        foreach ($group->students as $student) {
            if (!$student->email) {
                continue;
            }

            $this->notificationService->send(
                $student,
                new ExamPublishedNotification(
                    studentName:     $student->name,
                    channelName:     $channel?->name ?? 'Your Center',
                    examTitle:       $exam->title,
                    groupName:       $group->name,
                    startsAt:        $exam->starts_at?->toDateTimeString(),
                    endsAt:          $exam->ends_at?->toDateTimeString(),
                    durationMinutes: $exam->duration_minutes,
                ),
                $exam->channel_id,
                'exam_published'
            );
        }
    }
}
