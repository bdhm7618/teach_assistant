<?php

return [
    'created' => 'Attendance record created successfully.',
    'updated' => 'Attendance record updated successfully.',
    'deleted' => 'Attendance record deleted successfully.',
    'not_found' => 'Attendance record not found.',
    'operation_failed' => 'Something went wrong! Please try again.',
    'bulk_created' => 'Attendance records created successfully.',
    'statistics_retrieved' => 'Statistics retrieved successfully.',
    'live_retrieved'       => 'Live attendance retrieved successfully.',

    'qr' => [
        'invalid_token'    => 'Invalid or tampered QR token.',
        'token_expired'    => 'This QR code has expired. Ask the teacher to regenerate it.',
        'blocked_absent'   => 'You were manually marked absent for this session. Contact your teacher.',
        'already_checked_in' => 'You have already checked in for this session.',
        'checked_in'       => 'Attendance recorded successfully.',
    ],

    'status' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
    ],

    'validation' => [
        'student_not_in_group' => 'The selected student does not belong to the specified group.',
        'duplicate_attendance' => 'An attendance record already exists for this student, group, and date.',
    ],
];

