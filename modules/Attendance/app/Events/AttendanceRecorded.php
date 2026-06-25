<?php

namespace Modules\Attendance\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Attendance\App\Models\Attendance;

/**
 * Fired after any attendance record is created (manual mark or QR scan).
 * Wire up a broadcaster (Reverb / Pusher) in config/broadcasting.php to push
 * live updates to the teacher's session view. Until then, the live endpoint
 * provides the same data via polling.
 */
class AttendanceRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Attendance $attendance) {}
}
