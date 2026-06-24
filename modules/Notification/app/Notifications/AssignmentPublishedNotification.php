<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $studentName,
        public readonly string  $channelName,
        public readonly string  $assignmentTitle,
        public readonly string  $groupName,
        public readonly ?string $dueAt,
        public readonly bool    $allowLateSubmission,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("New Assignment — {$this->assignmentTitle}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("A new assignment has been published in **{$this->groupName}** at **{$this->channelName}**.")
            ->line("**Assignment:** {$this->assignmentTitle}");

        if ($this->dueAt) {
            $lateNote = $this->allowLateSubmission
                ? ' (late submissions accepted)'
                : ' (no late submissions)';
            $mail->line("**Due:** {$this->dueAt}{$lateNote}");
        }

        return $mail
            ->line('Log in to your student portal to view and submit the assignment.')
            ->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'assignment_published',
            'assignment_title' => $this->assignmentTitle,
            'group_name'       => $this->groupName,
            'due_at'           => $this->dueAt,
        ];
    }
}
