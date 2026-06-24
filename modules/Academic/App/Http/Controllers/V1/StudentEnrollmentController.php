<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\StudentEnrollmentRepository;
use Modules\Academic\App\Http\Requests\V1\StudentEnrollmentRequest;
use Modules\Academic\App\Http\Resources\V1\StudentEnrollmentResource;
use Modules\Payment\App\Models\Invoice;

/**
 * @OA\Tag(name="Enrollments", description="Student enrollment into groups — nested fees and first-invoice proration")
 */
class StudentEnrollmentController extends BaseController
{
    protected StudentEnrollmentRepository $repository;

    public function __construct(StudentEnrollmentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return StudentEnrollmentResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student-enrollments",
     *     summary="List enrollments",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="group_id",   in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status",     in="query", required=false, @OA\Schema(type="string", enum={"active","paused","canceled","completed"})),
     *     @OA\Parameter(name="per_page",   in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Enrollment list"),
     *     @OA\Response(response=403, description="Requires students.view")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->has('group_id')) {
                $query->where('group_id', $request->group_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $query->with(['student', 'group', 'group.subject', 'group.classGrade']);
            $enrollments = $query->paginate($request->get('per_page', 15));

            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student-enrollments",
     *     summary="Enroll a student into a group",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","group_id","enrollment_type","start_date"},
     *             @OA\Property(property="student_id",        type="integer"),
     *             @OA\Property(property="group_id",          type="integer"),
     *             @OA\Property(property="enrollment_type",   type="string", enum={"monthly","per_course","per_session"}),
     *             @OA\Property(property="start_date",        type="string", format="date"),
     *             @OA\Property(property="end_date",          type="string", format="date", nullable=true),
     *             @OA\Property(property="agreed_monthly_fee",type="number", nullable=true, description="Required for monthly type"),
     *             @OA\Property(property="agreed_course_fee", type="number", nullable=true, description="Required for per_course type"),
     *             @OA\Property(property="agreed_session_fee",type="number", nullable=true, description="Required for per_session type"),
     *             @OA\Property(property="sessions_per_month",type="integer", nullable=true),
     *             @OA\Property(property="notes",             type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Enrolled — first-month invoice auto-created (prorated if mid-month)"),
     *     @OA\Response(response=403, description="Requires students.create"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StudentEnrollmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $enrollment = $this->repository->create($request->validated());

            // Auto-create first invoice for monthly enrollments (prorated if mid-month)
            if ($enrollment->enrollment_type === 'monthly' && $enrollment->agreed_monthly_fee) {
                $this->createFirstMonthInvoice($enrollment);
            }

            DB::commit();
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group'])),
                trans('academic::app.enrollment.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student-enrollments/{id}",
     *     summary="Get a single enrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Enrollment data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            $enrollment = $this->repository->find($id);
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group', 'group.subject', 'group.classGrade'])),
                trans('academic::app.enrollment.retrieved')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/student-enrollments/{id}",
     *     summary="Update an enrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="status", type="string", enum={"active","paused","canceled","completed"}),
     *         @OA\Property(property="end_date", type="string", format="date", nullable=true),
     *         @OA\Property(property="agreed_monthly_fee", type="number", nullable=true),
     *         @OA\Property(property="sessions_per_month", type="integer", nullable=true),
     *         @OA\Property(property="notes", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Requires students.update"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(StudentEnrollmentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $enrollment = $this->repository->update($request->validated(), $id);

            if ($request->has('sessions_per_month')) {
                $enrollment->updateRemainingSessions();
            }

            DB::commit();
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group'])),
                trans('academic::app.enrollment.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/student-enrollments/{id}",
     *     summary="Cancel / remove an enrollment",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=403, description="Requires students.delete"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->repository->delete($id);
            DB::commit();
            return successResponse(null, trans('academic::app.enrollment.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/students/{studentId}/enrollments",
     *     summary="List all enrollments for a student",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="studentId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Enrollment list"),
     *     @OA\Response(response=403, description="Requires students.view")
     * )
     */
    public function getByStudent($studentId)
    {
        try {
            $enrollments = $this->repository->getByStudent($studentId);
            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/groups/{groupId}/enrollments",
     *     summary="List all enrollments for a group",
     *     tags={"Enrollments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Enrollment list"),
     *     @OA\Response(response=403, description="Requires students.view")
     * )
     */
    public function getByGroup($groupId)
    {
        try {
            $enrollments = $this->repository->getByGroup($groupId);
            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    // ─── Proration helper ────────────────────────────────────────────────────

    private function createFirstMonthInvoice($enrollment): void
    {
        $start       = Carbon::parse($enrollment->start_date);
        $daysInMonth = $start->daysInMonth;
        $daysRemaining = $daysInMonth - $start->day + 1; // inclusive of start day

        $monthlyFee   = (float) $enrollment->agreed_monthly_fee;
        $proratedFee  = ($start->day === 1)
            ? $monthlyFee
            : round($monthlyFee * ($daysRemaining / $daysInMonth), 2);

        $channelId = $enrollment->channel_id;

        Invoice::create([
            'invoice_number'  => Invoice::generateInvoiceNumber($channelId),
            'student_id'      => $enrollment->student_id,
            'group_id'        => $enrollment->group_id,
            'enrollment_id'   => $enrollment->id,
            'total_amount'    => $proratedFee,
            'discount_amount' => 0,
            'final_amount'    => $proratedFee,
            'paid_amount'     => 0,
            'remaining_amount'=> $proratedFee,
            'due_date'        => $start->toDateString(),
            'issue_date'      => Carbon::today()->toDateString(),
            'status'          => 'pending',
            'type'            => 'monthly',
            'channel_id'      => $channelId,
        ]);
    }
}
