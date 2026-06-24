<?php

namespace Modules\Notification\App\Services;

use Illuminate\Notifications\Notification;
use Modules\Notification\App\Models\NotificationLog;
use Throwable;

class NotificationService
{
    /**
     * Send a notification to a notifiable (e.g. Student) and log it.
     *
     * @param  object      $notifiable   Any model using the Notifiable trait
     * @param  Notification $notification
     * @param  int         $channelId
     * @param  string      $type         Short event key (enrollment_confirmed, invoice_created, …)
     */
    public function send(object $notifiable, Notification $notification, int $channelId, string $type): void
    {
        $email = method_exists($notifiable, 'routeNotificationForMail')
            ? $notifiable->routeNotificationForMail($notification)
            : ($notifiable->email ?? null);

        $subject = $this->extractSubject($notification, $notifiable);

        try {
            $notifiable->notify($notification);

            NotificationLog::create([
                'channel_id'       => $channelId,
                'notifiable_type'  => get_class($notifiable),
                'notifiable_id'    => $notifiable->getKey(),
                'type'             => $type,
                'channel'          => 'email',
                'subject'          => $subject,
                'recipient_email'  => $email,
                'status'           => 'sent',
            ]);
        } catch (Throwable $e) {
            NotificationLog::create([
                'channel_id'       => $channelId,
                'notifiable_type'  => get_class($notifiable),
                'notifiable_id'    => $notifiable->getKey(),
                'type'             => $type,
                'channel'          => 'email',
                'subject'          => $subject,
                'recipient_email'  => $email,
                'status'           => 'failed',
                'error_message'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send the same notification to a collection of notifiables.
     */
    public function sendToMany(iterable $notifiables, Notification $notification, int $channelId, string $type): void
    {
        foreach ($notifiables as $notifiable) {
            // Re-instantiate if needed — some queued notifications store state
            $this->send($notifiable, clone $notification, $channelId, $type);
        }
    }

    private function extractSubject(Notification $notification, object $notifiable): ?string
    {
        try {
            $mailMessage = $notification->toMail($notifiable);
            return $mailMessage->subject ?? null;
        } catch (Throwable) {
            return null;
        }
    }
}
