<?php

namespace Modules\Notification\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\App\Events\InvoiceCreated;
use Modules\Notification\App\Notifications\InvoiceCreatedNotification;
use Modules\Notification\App\Services\NotificationService;

class SendInvoiceCreatedNotification implements ShouldQueue
{
    public function __construct(protected NotificationService $notificationService) {}

    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $student = $invoice->student;

        if (!$student || !$student->email) {
            return;
        }

        $channel = app()->has('current_channel') ? app('current_channel') : null;

        $this->notificationService->send(
            $student,
            new InvoiceCreatedNotification(
                studentName:  $student->name,
                channelName:  $channel?->name ?? 'Your Center',
                amount:       (float) $invoice->total_amount,
                invoiceType:  $invoice->type,
                dueDate:      $invoice->due_date?->toDateString(),
                reason:       $invoice->reason ?? null,
            ),
            $invoice->channel_id,
            'invoice_created'
        );
    }
}
