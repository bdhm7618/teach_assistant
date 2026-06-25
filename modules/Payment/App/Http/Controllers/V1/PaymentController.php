<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Payment\App\Http\Requests\V1\PaymentRequest;
use Modules\Payment\App\Http\Resources\V1\PaymentResource;

/**
 * @OA\Tag(name="Payments", description="Record and manage student payments — requires payments.* permissions")
 */
class PaymentController extends BaseController
{
    protected PaymentRepository $repository;

    public function __construct(PaymentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return PaymentResource::class;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/payments",
     *     summary="Record a new payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","amount","payment_method"},
     *             @OA\Property(property="student_id",      type="integer"),
     *             @OA\Property(property="group_id",        type="integer", nullable=true),
     *             @OA\Property(property="invoice_id",      type="integer", nullable=true),
     *             @OA\Property(property="installment_id",  type="integer", nullable=true),
     *             @OA\Property(property="payment_period_id",type="integer", nullable=true),
     *             @OA\Property(property="amount",          type="number",  example=500.00),
     *             @OA\Property(property="discount_amount", type="number",  nullable=true),
     *             @OA\Property(property="payment_date",    type="string",  format="date-time", nullable=true),
     *             @OA\Property(property="payment_method",  type="string",  enum={"cash","bank_transfer","vodafone_cash","orange_money","etisalat_cash","easy_pay","credit_card","debit_card","online","other"}),
     *             @OA\Property(property="status",          type="string",  enum={"pending","completed","failed","refunded","cancelled"}, nullable=true),
     *             @OA\Property(property="reference_number",type="string",  nullable=true),
     *             @OA\Property(property="transaction_id",  type="string",  nullable=true),
     *             @OA\Property(property="notes",           type="string",  nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payment recorded"),
     *     @OA\Response(response=403, description="Requires payments.create"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(PaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(new PaymentResource($payment), trans('payment::app.created'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/payments/{id}",
     *     summary="Update a payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="amount",          type="number"),
     *         @OA\Property(property="discount_amount", type="number", nullable=true),
     *         @OA\Property(property="payment_method",  type="string"),
     *         @OA\Property(property="notes",           type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Requires payments.update"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(PaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(new PaymentResource($payment), trans('payment::app.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/payments/{id}",
     *     summary="Delete a payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Deleted"),
     *     @OA\Response(response=403, description="Requires payments.delete"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findOrFail($id);
            $this->repository->delete($payment->id);
            DB::commit();
            return successResponse(null, trans('payment::app.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/payments/{id}/complete",
     *     summary="Mark a payment as completed",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="transaction_id", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Marked as completed"),
     *     @OA\Response(response=403, description="Requires payments.update"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function markAsCompleted(Request $request, $id)
    {
        $request->validate(['transaction_id' => 'nullable|string|max:255']);

        DB::beginTransaction();
        try {
            $payment = $this->repository->markAsCompleted($id, $request->input('transaction_id'));
            DB::commit();
            return successResponse(new PaymentResource($payment), trans('payment::app.completed'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/payments/{id}/refund",
     *     summary="Refund a completed payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="notes", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Refunded"),
     *     @OA\Response(response=403, description="Requires payments.update"),
     *     @OA\Response(response=422, description="Cannot refund — business rule violation")
     * )
     */
    public function refund(Request $request, $id)
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);

        DB::beginTransaction();
        try {
            $payment = $this->repository->refund($id, $request->input('notes'));
            DB::commit();
            return successResponse(new PaymentResource($payment), trans('payment::app.refunded'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/payments/student/{studentId}",
     *     summary="List payments for a student",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="studentId",    in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date",   in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date",     in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Payment list"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getByStudent(Request $request, $studentId)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate   = $request->input('end_date')   ? Carbon::parse($request->input('end_date'))   : null;

            $payments = $this->repository->getByStudent($studentId, $startDate, $endDate);
            return successResponse(PaymentResource::collection($payments), trans('payment::app.list_success'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/payments/group/{groupId}",
     *     summary="List payments for a group",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="groupId",      in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="start_date",   in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date",     in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Payment list"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getByGroup(Request $request, $groupId)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate   = $request->input('end_date')   ? Carbon::parse($request->input('end_date'))   : null;

            $payments = $this->repository->getByGroup($groupId, $startDate, $endDate);
            return successResponse(PaymentResource::collection($payments), trans('payment::app.list_success'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/payments/statistics",
     *     summary="Financial statistics for the channel",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date",   in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date",     in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Statistics object"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate   = $request->input('end_date')   ? Carbon::parse($request->input('end_date'))   : null;

            $statistics = $this->repository->getFinancialStatistics($startDate, $endDate);
            return successResponse($statistics, trans('payment::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/payments/student/{studentId}/summary",
     *     summary="Payment summary for a student",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="studentId",    in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Summary object"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getStudentSummary($studentId)
    {
        try {
            $summary = $this->repository->getStudentSummary($studentId);
            return successResponse($summary, trans('payment::app.summary_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }
}
