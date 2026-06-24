<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $studentName,
        public readonly string $channelName,
        public readonly float  $remainingAmount,
        public readonly string $dueDate,
        public readonly int    $daysOverdue,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Overdue — EGP {$this->remainingAmount} — {$this->channelName}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("You have an **overdue payment** at **{$this->channelName}**.")
            ->line("**Outstanding amount:** EGP {$this->remainingAmount}")
            ->line("**Original due date:** {$this->dueDate}")
            ->line("**Days overdue:** {$this->daysOverdue}")
            ->line('Please contact your center to settle this payment as soon as possible.')
            ->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'invoice_overdue',
            'student_name'     => $this->studentName,
            'remaining_amount' => $this->remainingAmount,
            'due_date'         => $this->dueDate,
            'days_overdue'     => $this->daysOverdue,
        ];
    }
}
