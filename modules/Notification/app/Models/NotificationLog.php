<?php

namespace Modules\Notification\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Channel\App\Traits\HasChannelScope;

class NotificationLog extends Model
{
    use HasChannelScope;

    protected $table = 'notification_logs';

    protected $fillable = [
        'channel_id',
        'notifiable_type',
        'notifiable_id',
        'type',
        'channel',
        'subject',
        'body',
        'recipient_email',
        'recipient_phone',
        'status',
        'error_message',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}
