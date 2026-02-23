<?php

namespace Modules\Attendance\App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status label
     */
    public function label(): string
    {
        return match($this) {
            self::PRESENT => trans('attendance::app.status.present'),
            self::ABSENT => trans('attendance::app.status.absent'),
            self::LATE => trans('attendance::app.status.late'),
            self::EXCUSED => trans('attendance::app.status.excused'),
        };
    }

    /**
     * Check if status is positive (present or excused)
     */
    public function isPositive(): bool
    {
        return in_array($this, [self::PRESENT, self::EXCUSED]);
    }
}

