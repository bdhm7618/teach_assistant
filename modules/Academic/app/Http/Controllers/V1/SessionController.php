<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\Session;
use Modules\Academic\App\Services\SessionService;
use Modules\Academic\App\Http\Requests\V1\SessionRequest;
use Modules\Academic\App\Http\Resources\V1\SessionResource;

/**
 * @OA\Tag(name="Sessions", description="Individual session instances — slug-scoped: /api/v1/{channel_slug}/groups/{group}/sessions")
 */
class SessionController extends Controller
{
    public function __construct(protected SessionService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions",
     *     summary="List sessions for a group",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"scheduled","live","completed","cancelled"})),
     *     @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="List of sessions"),
     *     @OA\Response(response=404, description="Group or channel not found")
     * )
     */
    public function index(Request $request, int $groupId)
    {
        try {
            $group = Group::findOrFail($groupId);
            $query = Session::where('group_id', $group->id);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('from')) {
                $query->whereDate('scheduled_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('scheduled_at', '<=', $request->to);
            }

            $sessions = $query->orderBy('scheduled_at')->paginate(20);

            return successResponse(
                SessionResource::collection($sessions)->response()->getData(true),
                'Sessions retrieved successfully'
            );
        } catch (\Exception $e) {
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions",
     *     summary="Create a one-off or recurring session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="scheduled_at", type="string", format="datetime", example="2026-06-10 09:00:00", description="Required for one-off sessions"),
     *             @OA\Property(property="duration_minutes", type="integer", example=60),
     *             @OA\Property(property="type", type="string", enum={"online","offline"}),
     *             @OA\Property(property="location", type="string", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="recurring_rule", type="object", nullable=true,
     *                 @OA\Property(property="day", type="string", enum={"saturday","sunday","monday","tuesday","wednesday","thursday","friday"}),
     *                 @OA\Property(property="start_time", type="string", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", example="10:30", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Session created (or recurring sessions queued)"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Group or channel not found")
     * )
     */
    public function store(SessionRequest $request, int $groupId)
    {
        DB::beginTransaction();
        try {
            $group = Group::findOrFail($groupId);
            $data  = $request->validated();

            if (isset($data['recurring_rule'])) {
                $sessionTime = $this->service->createRecurring($group, $data['recurring_rule']);
                DB::commit();
                return successResponse(
                    ['session_time_id' => $sessionTime->id, 'message' => 'Recurring sessions queued for 90 days'],
                    'Recurring schedule created',
                    201
                );
            }

            $session = $this->service->createOneOff($group, $data);
            DB::commit();

            return successResponse(new SessionResource($session), 'Session created', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions/{session}",
     *     summary="Get a session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="session", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Session details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $groupId, int $sessionId)
    {
        try {
            $session = Session::where('group_id', $groupId)->findOrFail($sessionId);
            return successResponse(new SessionResource($session), 'Session retrieved');
        } catch (\Exception $e) {
            return errorResponse('Session not found', null, 404);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions/{session}",
     *     summary="Update a session (only if scheduled)",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="session", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="scheduled_at", type="string", format="datetime"),
     *         @OA\Property(property="status", type="string", enum={"scheduled","live","completed","cancelled"}),
     *         @OA\Property(property="location", type="string"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Session updated"),
     *     @OA\Response(response=422, description="Cannot edit non-scheduled session")
     * )
     */
    public function update(Request $request, int $groupId, int $sessionId)
    {
        DB::beginTransaction();
        try {
            $session = Session::where('group_id', $groupId)->findOrFail($sessionId);

            if (!$session->canBeEdited()) {
                DB::rollBack();
                return errorResponse('Only scheduled sessions can be edited', null, 422);
            }

            $session->update($request->only(['scheduled_at', 'status', 'location', 'notes', 'duration_minutes', 'type']));
            DB::commit();

            return successResponse(new SessionResource($session), 'Session updated');
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    // ─── QR generation ───────────────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions/{session}/qr",
     *     summary="Generate (or regenerate) a signed QR token for a session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group",   in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="session", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="QR token — render this as a QR code on the frontend",
     *         @OA\JsonContent(
     *             @OA\Property(property="qr_token",      type="string"),
     *             @OA\Property(property="qr_expires_at", type="string", format="date-time"),
     *             @OA\Property(property="session_id",    type="integer")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Requires sessions.create"),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function generateQr(int $groupId, int $sessionId)
    {
        try {
            $session = Session::where('group_id', $groupId)->findOrFail($sessionId);
            $session = $session->refreshQrToken();

            return successResponse([
                'qr_token'      => $session->qr_token,
                'qr_expires_at' => $session->qr_expires_at->toDateTimeString(),
                'session_id'    => $session->id,
            ], 'QR token generated');
        } catch (\Exception $e) {
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/groups/{group}/sessions/{session}",
     *     summary="Cancel a session",
     *     tags={"Sessions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="session", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Session cancelled"),
     *     @OA\Response(response=422, description="Cannot cancel a completed session")
     * )
     */
    public function destroy(int $groupId, int $sessionId)
    {
        DB::beginTransaction();
        try {
            $session = Session::where('group_id', $groupId)->findOrFail($sessionId);
            $this->service->cancel($session);
            DB::commit();

            return successResponse(null, 'Session cancelled');
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse($e->getMessage(), null, 422);
        }
    }
}
