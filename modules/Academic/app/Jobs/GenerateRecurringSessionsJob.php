<?php

namespace Modules\Academic\App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Academic\App\Models\Session;
use Modules\Academic\App\Models\SessionTime;

class GenerateRecurringSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $sessionTimeId) {}

    public function handle(): void
    {
        $sessionTime = SessionTime::findOrFail($this->sessionTimeId);
        $group       = $sessionTime->group;

        $dayMap = [
            'saturday'  => Carbon::SATURDAY,
            'sunday'    => Carbon::SUNDAY,
            'monday'    => Carbon::MONDAY,
            'tuesday'   => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday'  => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
        ];

        $targetDow = $dayMap[$sessionTime->day] ?? null;
        if ($targetDow === null) return;

        $horizon = Carbon::now()->addDays(90);
        $next    = Carbon::now()->startOfDay()->next($targetDow);

        $sessions = [];
        while ($next->lte($horizon)) {
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $next->toDateString() . ' ' . $sessionTime->start_time,
                'Africa/Cairo'
            )->utc();

            $sessions[] = [
                'channel_id'       => $group->channel_id,
                'group_id'         => $group->id,
                'session_time_id'  => $sessionTime->id,
                'scheduled_at'     => $scheduledAt,
                'duration_minutes' => 60,
                'type'             => 'offline',
                'status'           => 'scheduled',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            $next->addWeek();
        }

        foreach (array_chunk($sessions, 50) as $chunk) {
            Session::insertOrIgnore($chunk);
        }
    }
}
