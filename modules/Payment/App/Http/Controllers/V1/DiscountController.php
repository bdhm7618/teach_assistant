<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\DiscountRepository;
use Modules\Payment\App\Http\Requests\V1\DiscountRequest;
use Modules\Payment\App\Http\Resources\V1\DiscountResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;

class DiscountController extends BaseController
{
    protected DiscountRepository $repository;

    public function __construct(DiscountRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return DiscountResource::class;
    }

    public function store(DiscountRequest $request)
    {
        DB::beginTransaction();
        try {
            $discount = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new DiscountResource($discount),
                trans('payment::app.discount.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function update(DiscountRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $discount = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(
                new DiscountResource($discount),
                trans('payment::app.discount.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.discount.not_found'),
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
            $discount = $this->repository->findOrFail($id);
            $this->repository->delete($discount->id);
            DB::commit();
            return successResponse(null, trans('payment::app.discount.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.discount.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $channelId = auth('user')->user()?->channel_id;
            $result = $this->repository->applyDiscount(
                $request->input('code'),
                $request->input('amount'),
                $channelId
            );

            if ($result['success']) {
                $result['discount'] = new DiscountResource($result['discount']);
            }

            return successResponse($result, $result['message']);
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getActive()
    {
        try {
            $channelId = auth('user')->user()?->channel_id;
            $discounts = $this->repository->getActive($channelId);
            return successResponse(
                DiscountResource::collection($discounts),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }
}

