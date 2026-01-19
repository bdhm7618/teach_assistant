<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\PaymentRepository;
use Modules\Payment\App\Http\Requests\V1\PaymentRequest;
use Modules\Payment\App\Http\Resources\V1\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Carbon\Carbon;

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

    public function store(PaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new PaymentResource($payment),
                trans('payment::app.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function update(PaymentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(
                new PaymentResource($payment),
                trans('payment::app.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

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
            return errorResponse(
                trans('payment::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function markAsCompleted(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $payment = $this->repository->markAsCompleted($id, $request->input('transaction_id'));
            DB::commit();
            return successResponse(
                new PaymentResource($payment),
                trans('payment::app.completed')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function refund(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $payment = $this->repository->refund($id, $request->input('notes'));
            DB::commit();
            return successResponse(
                new PaymentResource($payment),
                trans('payment::app.refunded')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse($e->getMessage(), null, 422);
        }
    }

    public function getByStudent(Request $request, $studentId)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $payments = $this->repository->getByStudent($studentId, $startDate, $endDate);
            return successResponse(
                PaymentResource::collection($payments),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getByGroup(Request $request, $groupId)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $payments = $this->repository->getByGroup($groupId, $startDate, $endDate);
            return successResponse(
                PaymentResource::collection($payments),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $statistics = $this->repository->getFinancialStatistics($startDate, $endDate);
            return successResponse($statistics, trans('payment::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

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

