<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $studentName,
        public readonly string  $channelName,
        public readonly string  $examTitle,
        public readonly string  $groupName,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly ?int    $durationMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("New Exam Available — {$this->examTitle}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("A new exam has been published in **{$this->groupName}** at **{$this->channelName}**.")
            ->line("**Exam:** {$this->examTitle}");

        if ($this->durationMinutes) {
            $mail->line("**Duration:** {$this->durationMinutes} minutes");
        }

        if ($this->startsAt) {
            $mail->line("**Opens:** {$this->startsAt}");
        }

        if ($this->endsAt) {
            $mail->line("**Closes:** {$this->endsAt}");
        }

        return $mail
            ->line('Log in to your student portal to take the exam.')
            ->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'exam_published',
            'exam_title' => $this->examTitle,
            'group_name' => $this->groupName,
            'starts_at'  => $this->startsAt,
            'ends_at'    => $this->endsAt,
        ];
    }
}
