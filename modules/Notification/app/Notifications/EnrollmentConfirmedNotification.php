<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $studentName,
        public readonly string $groupName,
        public readonly string $courseName,
        public readonly string $channelName,
        public readonly float  $firstInvoiceAmount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Enrollment Confirmed — {$this->groupName}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("You have been successfully enrolled in **{$this->groupName}** ({$this->courseName}) at **{$this->channelName}**.")
            ->line("Your first invoice of **EGP {$this->firstInvoiceAmount}** has been generated.")
            ->line('Please contact your center for payment details.')
            ->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'                => 'enrollment_confirmed',
            'student_name'        => $this->studentName,
            'group_name'          => $this->groupName,
            'course_name'         => $this->courseName,
            'first_invoice_amount'=> $this->firstInvoiceAmount,
        ];
    }
}
