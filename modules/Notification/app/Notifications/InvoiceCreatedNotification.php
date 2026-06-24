<?php

namespace Modules\Notification\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $studentName,
        public readonly string  $channelName,
        public readonly float   $amount,
        public readonly string  $invoiceType,
        public readonly ?string $dueDate,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = match ($this->invoiceType) {
            'monthly'        => 'Monthly Fee',
            'enrollment_fee' => 'Enrollment Fee',
            'session'        => 'Session Fee',
            'ad_hoc'         => 'Additional Fee',
            default          => 'Invoice',
        };

        $mail = (new MailMessage)
            ->subject("New Invoice — {$typeLabel} — EGP {$this->amount}")
            ->greeting("Hello, {$this->studentName}!")
            ->line("A new invoice has been issued at **{$this->channelName}**.")
            ->line("**Type:** {$typeLabel}")
            ->line("**Amount:** EGP {$this->amount}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        if ($this->dueDate) {
            $mail->line("**Due by:** {$this->dueDate}");
        }

        return $mail
            ->line('Please contact your center to arrange payment.')
            ->salutation("— {$this->channelName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'invoice_created',
            'student_name' => $this->studentName,
            'amount'       => $this->amount,
            'invoice_type' => $this->invoiceType,
            'due_date'     => $this->dueDate,
        ];
    }
}
