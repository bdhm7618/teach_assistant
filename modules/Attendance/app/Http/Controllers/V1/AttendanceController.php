<?php

namespace Modules\Attendance\App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Attendance\App\Repositories\AttendanceRepository;
use Modules\Attendance\App\Http\Requests\V1\AttendanceRequest;
use Modules\Attendance\App\Http\Resources\V1\AttendanceResource;
use Modules\Attendance\App\Enums\AttendanceStatus;
use Modules\Attendance\App\Events\AttendanceRecorded;
use Modules\Academic\App\Models\Session;

/**
 * @OA\Tag(name="Attendance", description="Attendance management — manual marking, QR scan, live session view")
 */
class AttendanceController extends BaseController
{
    // Late threshold: QR scan more than 15 min after scheduled_at → status = late
    private const LATE_THRESHOLD_MINUTES = 15;

    protected AttendanceRepository $repository;

    public function __construct(AttendanceRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return AttendanceResource::class;
    }

    // ─── Manual mark ─────────────────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/attendances",
     *     summary="Manually mark a student's attendance",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","group_id","date","status"},
     *             @OA\Property(property="student_id",     type="integer"),
     *             @OA\Property(property="group_id",       type="integer"),
     *             @OA\Property(property="session_id",     type="integer", nullable=true),
     *             @OA\Property(property="session_time_id",type="integer", nullable=true),
     *             @OA\Property(property="date",           type="string", format="date-time"),
     *             @OA\Property(property="status",         type="string", enum={"present","absent","late","excused"}),
     *             @OA\Property(property="notes",          type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Attendance recorded"),
     *     @OA\Response(response=403, description="Requires attendance.manage"),
     *     @OA\Response(response=422, description="Validation error or duplicate")
     * )
     */
    public function store(AttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->create($request->validated());
            event(new AttendanceRecorded($attendance));
            DB::commit();
            return successResponse(new AttendanceResource($attendance), trans('attendance::app.created'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/attendances/{id}",
     *     summary="Update an attendance record",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="status", type="string", enum={"present","absent","late","excused"}),
     *         @OA\Property(property="notes",  type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Requires attendance.manage"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(AttendanceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->findOrFail($id);
            $attendance = $this->repository->update($request->validated(), $attendance->id);
            DB::commit();
            return successResponse(new AttendanceResource($attendance), trans('attendance::app.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/attendances/{id}",
     *     summary="Delete an attendance record",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=403, description="Requires attendance.manage"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->findOrFail($id);
            $this->repository->delete($attendance->id);
            DB::commit();
            return successResponse(null, trans('attendance::app.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    // ─── Bulk mark ───────────────────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/attendances/bulk",
     *     summary="Bulk-mark attendance for a session",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"attendances"},
     *             @OA\Property(property="session_id", type="integer", nullable=true),
     *             @OA\Property(property="attendances", type="array", @OA\Items(
     *                 @OA\Property(property="student_id", type="integer"),
     *                 @OA\Property(property="group_id",   type="integer"),
     *                 @OA\Property(property="status",     type="string", enum={"present","absent","late","excused"}),
     *                 @OA\Property(property="date",       type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="notes",      type="string", nullable=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Attendance records created"),
     *     @OA\Response(response=403, description="Requires attendance.manage")
     * )
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'session_id'                  => 'nullable|integer|exists:group_sessions,id',
            'attendances'                 => 'required|array|min:1',
            'attendances.*.student_id'    => 'required|integer|exists:students,id',
            'attendances.*.group_id'      => 'required|integer|exists:groups,id',
            'attendances.*.date'          => 'nullable|date',
            'attendances.*.status'        => 'required|string|in:present,absent,late,excused',
            'attendances.*.notes'         => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $channelId = app('current_channel_id');
            $sessionId = $request->input('session_id');

            $records = collect($request->input('attendances'))->map(function ($row) use ($channelId, $sessionId) {
                $row['channel_id'] = $channelId;
                $row['session_id'] = $sessionId;
                if (! isset($row['date'])) {
                    $row['date'] = Carbon::now();
                }
                return $row;
            })->toArray();

            $created = $this->repository->bulkCreate($records);
            DB::commit();
            return successResponse(AttendanceResource::collection($created), trans('attendance::app.bulk_created'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    // ─── QR scan ─────────────────────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/attendances/qr-scan",
     *     summary="Student scans QR code — auto-detects present vs late",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","student_id"},
     *             @OA\Property(property="token",      type="string", description="QR token from session QR endpoint"),
     *             @OA\Property(property="student_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Attendance recorded — status auto-set to present or late"),
     *     @OA\Response(response=400, description="Invalid or expired QR token"),
     *     @OA\Response(response=409, description="Student was manually marked absent — QR scan blocked"),
     *     @OA\Response(response=422, description="Student already checked in for this session")
     * )
     */
    public function qrScan(Request $request)
    {
        $request->validate([
            'token'      => 'required|string',
            'student_id' => 'required|integer|exists:students,id',
        ]);

        $token     = $request->input('token');
        $studentId = (int) $request->input('student_id');

        // 1. Verify token signature
        $parsed = Session::parseQrToken($token);
        if (! $parsed) {
            return errorResponse(trans('attendance::app.qr.invalid_token'), null, 400);
        }

        // 2. Check token expiry
        if (Carbon::now()->timestamp > $parsed['expires_at']) {
            return errorResponse(trans('attendance::app.qr.token_expired'), null, 400);
        }

        // 3. Load the session and verify it belongs to the current channel
        $session = Session::where('id', $parsed['session_id'])
            ->where('channel_id', app('current_channel_id'))
            ->where('qr_token', $token)   // token must still match (not regenerated)
            ->first();

        if (! $session) {
            return errorResponse(trans('attendance::app.qr.invalid_token'), null, 400);
        }

        // 4. Block: student was manually marked absent for this session
        $existing = \Modules\Attendance\App\Models\Attendance::where('student_id', $studentId)
            ->where('session_id', $session->id)
            ->first();

        if ($existing) {
            if ($existing->status->value === AttendanceStatus::ABSENT->value) {
                // Locked decision: QR scan after manual 'absent' → BLOCK
                return errorResponse(trans('attendance::app.qr.blocked_absent'), null, 409);
            }
            // Already checked in
            return errorResponse(trans('attendance::app.qr.already_checked_in'), null, 422);
        }

        // 5. Determine status: present if within 15-min window, late otherwise
        $now    = Carbon::now();
        $cutoff = $session->scheduled_at->copy()->addMinutes(self::LATE_THRESHOLD_MINUTES);
        $status = $now->lte($cutoff) ? AttendanceStatus::PRESENT : AttendanceStatus::LATE;

        DB::beginTransaction();
        try {
            $attendance = $this->repository->create([
                'student_id'  => $studentId,
                'group_id'    => $session->group_id,
                'session_id'  => $session->id,
                'date'        => $now,
                'status'      => $status->value,
                'channel_id'  => $session->channel_id,
            ]);
            event(new AttendanceRecorded($attendance));
            DB::commit();
            return successResponse(new AttendanceResource($attendance), trans('attendance::app.qr.checked_in'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    // ─── Session live view (polling endpoint for realtime) ───────────────────

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/sessions/{session}/attendance",
     *     summary="Live attendance for a session — poll this for realtime updates",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="session",      in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Current attendance state with summary counts"),
     *     @OA\Response(response=403, description="Requires attendance.view"),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function sessionLive($channelSlug, $sessionId)
    {
        try {
            $session = Session::where('id', $sessionId)
                ->where('channel_id', app('current_channel_id'))
                ->firstOrFail();

            $records = $this->repository->getBySession($session->id);

            $summary = [
                'total'    => $records->count(),
                'present'  => $records->filter(fn ($a) => $a->status->value === 'present')->count(),
                'late'     => $records->filter(fn ($a) => $a->status->value === 'late')->count(),
                'absent'   => $records->filter(fn ($a) => $a->status->value === 'absent')->count(),
                'excused'  => $records->filter(fn ($a) => $a->status->value === 'excused')->count(),
            ];

            return successResponse([
                'session'  => [
                    'id'           => $session->id,
                    'scheduled_at' => $session->scheduled_at->toDateTimeString(),
                    'status'       => $session->status,
                    'qr_expires_at'=> $session->qr_expires_at?->toDateTimeString(),
                ],
                'summary'    => $summary,
                'attendance' => AttendanceResource::collection($records),
            ], trans('attendance::app.live_retrieved'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    // ─── Statistics ──────────────────────────────────────────────────────────

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/attendances/statistics/student/{studentId}",
     *     summary="Attendance statistics for a student",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="studentId",    in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date",   in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date",     in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Statistics: total, present, absent, late, excused, attendance_rate"),
     *     @OA\Response(response=403, description="Requires attendance.view")
     * )
     */
    public function studentStatistics(Request $request, $studentId)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate   = $request->input('end_date')   ? Carbon::parse($request->input('end_date'))   : null;
            $stats     = $this->repository->getStudentStatistics($studentId, $startDate, $endDate);
            return successResponse($stats, trans('attendance::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/attendances/statistics/group/{groupId}",
     *     summary="Attendance statistics for a group",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date",    in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Statistics object"),
     *     @OA\Response(response=403, description="Requires attendance.view")
     * )
     */
    public function groupStatistics(Request $request, $groupId)
    {
        try {
            $date  = $request->input('date') ? Carbon::parse($request->input('date')) : null;
            $stats = $this->repository->getGroupStatistics($groupId, $date);
            return successResponse($stats, trans('attendance::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }
}
