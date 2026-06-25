<?php

namespace Modules\StudentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Academic\App\Models\Session;
use Modules\Academic\App\Models\StudentEnrollment;
use Modules\Attendance\App\Models\Attendance;
use Modules\StudentPortal\App\Http\Resources\V1\EnrollmentResource;
use Modules\StudentPortal\App\Http\Resources\V1\SessionResource;

/**
 * @OA\Tag(name="Student Dashboard", description="Student portal — overview, enrollments, sessions, attendance")
 */
class StudentDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/enrollments",
     *     summary="List student's active and past enrollments",
     *     tags={"Student Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Filter: active|inactive|all (default: active)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Enrollment list")
     * )
     */
    public function enrollments(Request $request): JsonResponse
    {
        $student   = auth('student')->user();
        $statusFilter = $request->input('status', 'active');

        $query = StudentEnrollment::where('student_id', $student->id)
            ->with('group');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $enrollments = $query->latest()->get();

        return successResponse(
            EnrollmentResource::collection($enrollments),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/sessions/upcoming",
     *     summary="List upcoming sessions for student's enrolled groups",
     *     tags={"Student Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="days", in="query", description="Look ahead days (default: 30)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Upcoming sessions")
     * )
     */
    public function upcomingSessions(Request $request): JsonResponse
    {
        $student  = auth('student')->user();
        $days     = (int) $request->input('days', 30);
        $perPage  = (int) $request->input('per_page', 20);

        $groupIds = StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->pluck('group_id');

        $sessions = Session::whereIn('group_id', $groupIds)
            ->whereIn('status', ['scheduled', 'live'])
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays($days))
            ->with('group')
            ->orderBy('scheduled_at')
            ->paginate($perPage);

        $sessionIds = $sessions->pluck('id');
        $attendanceMap = Attendance::where('student_id', $student->id)
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->keyBy('session_id');

        $sessions->getCollection()->transform(function ($session) use ($attendanceMap) {
            $attendance = $attendanceMap->get($session->id);
            $session->my_attendance_status = $attendance ? $attendance->status->value : null;
            return $session;
        });

        return successResponse(
            SessionResource::collection($sessions)->response()->getData(true),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/sessions",
     *     summary="List all past and upcoming sessions for student with attendance status",
     *     tags={"Student Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from", in="query", description="Date YYYY-MM-DD", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Date YYYY-MM-DD", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Sessions list with attendance status")
     * )
     */
    public function sessions(Request $request): JsonResponse
    {
        $student = auth('student')->user();
        $perPage = (int) $request->input('per_page', 20);

        $groupIds = StudentEnrollment::where('student_id', $student->id)
            ->pluck('group_id');

        $query = Session::whereIn('group_id', $groupIds)
            ->with('group')
            ->when($request->input('group_id'), fn($q, $v) => $q->where('group_id', $v))
            ->when($request->input('from'),     fn($q, $v) => $q->where('scheduled_at', '>=', $v))
            ->when($request->input('to'),       fn($q, $v) => $q->where('scheduled_at', '<=', $v . ' 23:59:59'))
            ->orderByDesc('scheduled_at');

        $sessions = $query->paginate($perPage);

        $sessionIds    = $sessions->pluck('id');
        $attendanceMap = Attendance::where('student_id', $student->id)
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->keyBy('session_id');

        $sessions->getCollection()->transform(function ($session) use ($attendanceMap) {
            $attendance = $attendanceMap->get($session->id);
            $session->my_attendance_status = $attendance ? $attendance->status->value : null;
            return $session;
        });

        return successResponse(
            SessionResource::collection($sessions)->response()->getData(true),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/attendance/summary",
     *     summary="Get attendance summary per group",
     *     tags={"Student Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Attendance summary")
     * )
     */
    public function attendanceSummary(Request $request): JsonResponse
    {
        $student  = auth('student')->user();

        $enrollments = StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->with('group')
            ->get();

        $summary = $enrollments->map(function ($enrollment) use ($student) {
            $groupId = $enrollment->group_id;

            $total    = Attendance::where('student_id', $student->id)->where('group_id', $groupId)->count();
            $present  = Attendance::where('student_id', $student->id)->where('group_id', $groupId)->where('status', 'present')->count();
            $absent   = Attendance::where('student_id', $student->id)->where('group_id', $groupId)->where('status', 'absent')->count();
            $late     = Attendance::where('student_id', $student->id)->where('group_id', $groupId)->where('status', 'late')->count();

            return [
                'group_id'         => $groupId,
                'group_name'       => $enrollment->group?->name,
                'total_sessions'   => $total,
                'present'          => $present,
                'absent'           => $absent,
                'late'             => $late,
                'attendance_rate'  => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
            ];
        });

        return successResponse($summary, __('studentportal::app.show_success'));
    }
}
