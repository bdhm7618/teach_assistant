<?php

namespace Modules\Academic\App\Services;

use Carbon\Carbon;
use Modules\Academic\App\Jobs\GenerateRecurringSessionsJob;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\Session;
use Modules\Academic\App\Models\SessionTime;

class SessionService
{
    public function createOneOff(Group $group, array $data): Session
    {
        return Session::create([
            'channel_id'       => $group->channel_id,
            'group_id'         => $group->id,
            'session_time_id'  => null,
            'scheduled_at'     => Carbon::parse($data['scheduled_at'])->utc(),
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'type'             => $data['type'] ?? 'offline',
            'location'         => $data['location'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);
    }

    public function createRecurring(Group $group, array $rule): SessionTime
    {
        $sessionTime = SessionTime::create([
            'channel_id' => $group->channel_id,
            'group_id'   => $group->id,
            'day'        => $rule['day'],
            'start_time' => $rule['start_time'],
            'end_time'   => $rule['end_time'] ?? null,
            'is_active'  => true,
        ]);

        GenerateRecurringSessionsJob::dispatch($sessionTime->id);

        return $sessionTime;
    }

    public function cancel(Session $session): Session
    {
        if (!$session->canBeEdited()) {
            abort(422, 'Cannot cancel a completed or live session.');
        }
        $session->update(['status' => 'cancelled']);
        return $session;
    }

    public function complete(Session $session): Session
    {
        $session->update(['status' => 'completed']);
        return $session;
    }
}
