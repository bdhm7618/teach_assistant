<?php

namespace Modules\Notification\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id'   => $this->notifiable_id,
            'type'            => $this->type,
            'channel'         => $this->channel,
            'subject'         => $this->subject,
            'recipient_email' => $this->recipient_email,
            'status'          => $this->status,
            'error_message'   => $this->when($this->status === 'failed', $this->error_message),
            'created_at'      => $this->created_at->toIso8601String(),
        ];
    }
}
