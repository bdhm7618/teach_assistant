<?php

namespace Modules\Notification\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notification\App\Notifications\SubmissionGradedNotification;
use Modules\Notification\App\Services\NotificationService;

class SendSubmissionGradedNotification implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Handles both ExamSubmission and AssignmentSubmission graded events.
     * Both events expose a $submission property with the same interface.
     */
    public function handle(object $event): void
    {
        $submission = $event->submission;
        $student    = $submission->student;

        if (!$student || !$student->email) {
            return;
        }

        $channel  = app()->has('current_channel') ? app('current_channel') : null;
        $isExam   = $submission instanceof \Modules\Exam\App\Models\ExamSubmission;
        $parent   = $isExam ? $submission->exam : $submission->assignment;

        $this->notificationService->send(
            $student,
            new SubmissionGradedNotification(
                studentName:    $student->name,
                channelName:    $channel?->name ?? 'Your Center',
                subjectTitle:   $parent?->title ?? '',
                subjectType:    $isExam ? 'exam' : 'assignment',
                marksObtained:  (float) $submission->marks_obtained,
                totalMarks:     (float) ($parent?->total_marks ?? 100),
                isPass:         (bool)  $submission->is_pass,
                feedback:       $isExam
                                    ? ($submission->teacher_notes ?? null)
                                    : ($submission->teacher_feedback ?? null),
            ),
            $submission->channel_id,
            'submission_graded'
        );
    }
}
