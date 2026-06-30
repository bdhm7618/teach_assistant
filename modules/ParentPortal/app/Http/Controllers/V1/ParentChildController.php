<?php

namespace Modules\ParentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Student\App\Models\Student;
use Modules\Academic\App\Models\Session;
use Modules\Academic\App\Models\StudentEnrollment;
use Modules\Attendance\App\Models\Attendance;
use Modules\Exam\App\Models\Exam;
use Modules\Exam\App\Models\ExamSubmission;
use Modules\Assignment\App\Models\Assignment;
use Modules\Assignment\App\Models\AssignmentSubmission;
use Modules\Payment\App\Models\Invoice;
use Modules\ParentPortal\App\Http\Resources\V1\ChildResource;
use Modules\ParentPortal\App\Http\Resources\V1\EnrollmentResource;
use Modules\ParentPortal\App\Http\Resources\V1\SessionResource;
use Modules\ParentPortal\App\Http\Resources\V1\ExamResource;
use Modules\ParentPortal\App\Http\Resources\V1\AssignmentResource;
use Modules\ParentPortal\App\Http\Resources\V1\InvoiceResource;

/**
 * @OA\Tag(name="Parent Children", description="Parent portal — children list and per-child read-only views")
 */
class ParentChildController extends Controller
{
    /**
     * Resolve a child the authenticated parent owns, or abort 403/404.
     */
    private function resolveChild(int $studentId): Student
    {
        $parent = auth('parent')->user();

        $student = $parent->students()
            ->where('students.id', $studentId)
            ->first();

        if (! $student) {
            abort(response()->json([
                'status'  => 'error',
                'message' => __('parentportal::app.child_not_linked'),
                'errors'  => __('parentportal::app.child_not_linked'),
            ], 403));
        }

        return $student;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children",
     *     summary="List the parent's linked children",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Children list")
     * )
     */
    public function index(): JsonResponse
    {
        $parent   = auth('parent')->user();
        $children = $parent->students()->get();

        return successResponse(
            ChildResource::collection($children),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parent/children/claim",
     *     summary="Claim a child by student code (verified by the student's phone on file)",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_code","verify_phone"},
     *             @OA\Property(property="student_code", type="string"),
     *             @OA\Property(property="verify_phone", type="string", description="Student's phone OR a guardian phone on file"),
     *             @OA\Property(property="relationship", type="string", enum={"father","mother","brother","sister","uncle","aunt","other"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Child linked"),
     *     @OA\Response(response=404, description="No student matches that code"),
     *     @OA\Response(response=422, description="Verification failed or already linked")
     * )
     */
    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'student_code' => 'required|string',
            'verify_phone' => 'required|string',
            'relationship' => 'sometimes|in:father,mother,brother,sister,uncle,aunt,other',
        ]);

        $parent    = auth('parent')->user();
        $channelId = $parent->channel_id;

        $student = Student::where('channel_id', $channelId)
            ->where('code', $request->student_code)
            ->first();

        if (! $student) {
            return errorResponse(__('parentportal::app.child_code_not_found'), null, 404);
        }

        // Proof of relationship: the supplied phone must match the student's own
        // phone OR a guardian phone already on file for that student.
        $phone        = preg_replace('/\s+/', '', $request->verify_phone);
        $studentPhone = preg_replace('/\s+/', '', (string) $student->phone);
        $matchesStudent  = $studentPhone !== '' && $studentPhone === $phone;
        $matchesGuardian = $student->guardians()
            ->whereRaw("REPLACE(phone, ' ', '') = ?", [$phone])
            ->exists();

        if (! $matchesStudent && ! $matchesGuardian) {
            return errorResponse(__('parentportal::app.child_verify_failed'), null, 422);
        }

        if ($parent->students()->where('students.id', $student->id)->exists()) {
            return errorResponse(__('parentportal::app.child_already_linked'), null, 422);
        }

        $isFirst = $parent->students()->count() === 0;

        $parent->students()->attach($student->id, [
            'channel_id'   => $channelId,
            'relationship' => $request->input('relationship', 'father'),
            'is_primary'   => $isFirst,
        ]);

        return successResponse(
            new ChildResource($student->fresh()),
            __('parentportal::app.child_linked'),
            201
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}",
     *     summary="Unlink a child from this parent account",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Child unlinked"),
     *     @OA\Response(response=403, description="Child not linked to this parent")
     * )
     */
    public function unclaim(int $studentId): JsonResponse
    {
        $parent = auth('parent')->user();
        $child  = $this->resolveChild($studentId);

        $parent->students()->detach($child->id);

        return successResponse(null, __('parentportal::app.child_unlinked'));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/enrollments",
     *     summary="A child's enrollments",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="active|inactive|all (default active)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Enrollment list"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function enrollments(Request $request, int $studentId): JsonResponse
    {
        $child  = $this->resolveChild($studentId);
        $status = $request->input('status', 'active');

        $query = StudentEnrollment::where('student_id', $child->id)->with('group');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return successResponse(
            EnrollmentResource::collection($query->latest()->get()),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/sessions",
     *     summary="A child's sessions with attendance status",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="upcoming", in="query", description="1 = only upcoming scheduled/live", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Sessions list"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function sessions(Request $request, int $studentId): JsonResponse
    {
        $child   = $this->resolveChild($studentId);
        $perPage = (int) $request->input('per_page', 20);

        $groupIds = StudentEnrollment::where('student_id', $child->id)->pluck('group_id');

        $query = Session::whereIn('group_id', $groupIds)->with('group');

        if ($request->boolean('upcoming')) {
            $query->whereIn('status', ['scheduled', 'live'])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at');
        } else {
            $query->orderByDesc('scheduled_at');
        }

        $sessions = $query->paginate($perPage);

        $attendanceMap = Attendance::where('student_id', $child->id)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->get()
            ->keyBy('session_id');

        $sessions->getCollection()->transform(function ($session) use ($attendanceMap) {
            $attendance = $attendanceMap->get($session->id);
            $session->my_attendance_status = $attendance
                ? ($attendance->status instanceof \BackedEnum ? $attendance->status->value : $attendance->status)
                : null;
            return $session;
        });

        return successResponse(
            SessionResource::collection($sessions)->response()->getData(true),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/attendance/summary",
     *     summary="A child's attendance summary per group",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Attendance summary"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function attendanceSummary(int $studentId): JsonResponse
    {
        $child = $this->resolveChild($studentId);

        $enrollments = StudentEnrollment::where('student_id', $child->id)
            ->where('status', 'active')
            ->with('group')
            ->get();

        $summary = $enrollments->map(function ($enrollment) use ($child) {
            $groupId = $enrollment->group_id;
            $base    = Attendance::where('student_id', $child->id)->where('group_id', $groupId);

            $total   = (clone $base)->count();
            $present = (clone $base)->where('status', 'present')->count();
            $absent  = (clone $base)->where('status', 'absent')->count();
            $late    = (clone $base)->where('status', 'late')->count();

            return [
                'group_id'        => $groupId,
                'group_name'      => $enrollment->group?->name,
                'total_sessions'  => $total,
                'present'         => $present,
                'absent'          => $absent,
                'late'            => $late,
                'attendance_rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
            ];
        });

        return successResponse($summary, __('parentportal::app.show_success'));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/exams",
     *     summary="A child's exams with attempt results (read-only)",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam list with results"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function exams(int $studentId): JsonResponse
    {
        $child = $this->resolveChild($studentId);

        $groupIds = StudentEnrollment::where('student_id', $child->id)->pluck('group_id');

        $exams = Exam::whereIn('group_id', $groupIds)
            ->where('status', '!=', 'draft')
            ->with('group')
            ->orderByDesc('starts_at')
            ->get();

        $exams->each(function ($exam) use ($child) {
            $latest = ExamSubmission::where('exam_id', $exam->id)
                ->where('student_id', $child->id)
                ->orderByDesc('created_at')
                ->first();

            $exam->my_latest_attempt = $latest ? [
                'id'             => $latest->id,
                'status'         => $latest->status,
                'attempt_number' => $latest->attempt_number,
                'obtained_marks' => $latest->obtained_marks,
                'is_pass'        => $latest->is_pass,
                'submitted_at'   => $latest->submitted_at,
            ] : null;
        });

        return successResponse(
            ExamResource::collection($exams),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/assignments",
     *     summary="A child's assignments with submission status (read-only)",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment list with submission status"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function assignments(int $studentId): JsonResponse
    {
        $child = $this->resolveChild($studentId);

        $groupIds = StudentEnrollment::where('student_id', $child->id)->pluck('group_id');

        $assignments = Assignment::whereIn('group_id', $groupIds)
            ->where('status', '!=', 'draft')
            ->with('group')
            ->orderByDesc('due_at')
            ->get();

        $assignments->each(function ($assignment) use ($child) {
            $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('student_id', $child->id)
                ->first();

            $assignment->my_submission = $submission ? [
                'id'             => $submission->id,
                'status'         => $submission->status,
                'marks_obtained' => $submission->marks_obtained,
                'submitted_at'   => $submission->submitted_at,
                'is_late'        => $submission->is_late,
            ] : null;
        });

        return successResponse(
            AssignmentResource::collection($assignments),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/invoices",
     *     summary="A child's invoices",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", description="pending|paid|overdue|all", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Invoice list"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function invoices(Request $request, int $studentId): JsonResponse
    {
        $child   = $this->resolveChild($studentId);
        $perPage = (int) $request->input('per_page', 15);
        $status  = $request->input('status');

        $invoices = Invoice::where('student_id', $child->id)
            ->with('group')
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('issue_date')
            ->paginate($perPage);

        return successResponse(
            InvoiceResource::collection($invoices)->response()->getData(true),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parent/children/{student_id}/invoices/summary",
     *     summary="A child's payment summary",
     *     tags={"Parent Children"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Payment summary"),
     *     @OA\Response(response=403, description="Child not linked")
     * )
     */
    public function invoiceSummary(int $studentId): JsonResponse
    {
        $child    = $this->resolveChild($studentId);
        $invoices = Invoice::where('student_id', $child->id)->get();

        $summary = [
            'total_invoiced'  => $invoices->sum('final_amount'),
            'total_paid'      => $invoices->sum('paid_amount'),
            'total_remaining' => $invoices->sum('remaining_amount'),
            'pending_count'   => $invoices->where('status', 'pending')->count(),
            'overdue_count'   => $invoices->where('status', 'overdue')->count(),
            'paid_count'      => $invoices->where('status', 'paid')->count(),
        ];

        return successResponse($summary, __('parentportal::app.show_success'));
    }
}
