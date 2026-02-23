<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\PaymentPeriodRepository;
use Modules\Payment\App\Http\Requests\V1\PaymentPeriodRequest;
use Modules\Payment\App\Http\Resources\V1\PaymentPeriodResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Carbon\Carbon;

class PaymentPeriodController extends BaseController
{
    protected PaymentPeriodRepository $repository;

    public function __construct(PaymentPeriodRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return PaymentPeriodResource::class;
    }

    public function store(PaymentPeriodRequest $request)
    {
        DB::beginTransaction();
        try {
            $period = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new PaymentPeriodResource($period),
                trans('payment::app.period.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function createMonthly(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        DB::beginTransaction();
        try {
            $period = $this->repository->getOrCreateMonthly(
                $request->input('year'),
                $request->input('month')
            );
            DB::commit();
            return successResponse(
                new PaymentPeriodResource($period),
                trans('payment::app.period.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function createWeekly(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        DB::beginTransaction();
        try {
            $period = $this->repository->getOrCreateWeekly(
                Carbon::parse($request->input('start_date')),
                Carbon::parse($request->input('end_date'))
            );
            DB::commit();
            return successResponse(
                new PaymentPeriodResource($period),
                trans('payment::app.period.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function update(PaymentPeriodRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $period = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(
                new PaymentPeriodResource($period),
                trans('payment::app.period.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.period.not_found'),
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
            $period = $this->repository->findOrFail($id);
            $this->repository->delete($period->id);
            DB::commit();
            return successResponse(null, trans('payment::app.period.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.period.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getOpenPeriods()
    {
        try {
            $periods = $this->repository->getOpenPeriods();
            return successResponse(
                PaymentPeriodResource::collection($periods),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getCurrentPeriod()
    {
        try {
            $period = $this->repository->getCurrentPeriod();
            if (!$period) {
                return errorResponse(
                    trans('payment::app.period.no_current_period'),
                    null,
                    404
                );
            }
            return successResponse(
                new PaymentPeriodResource($period),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getStatistics($id)
    {
        try {
            $statistics = $this->repository->getPeriodStatistics($id);
            return successResponse($statistics, trans('payment::app.statistics_retrieved'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('payment::app.period.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }
}

