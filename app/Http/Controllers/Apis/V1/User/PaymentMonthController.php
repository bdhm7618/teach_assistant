<?php

namespace App\Http\Controllers\Apis\Teacher;

use App\Models\PaymentMonth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\PaymentMonthRequest;
use App\Http\Resources\Payment\PaymentMonthResource;

class PaymentMonthController extends Controller
{
    public function index()
    {
        $months = PaymentMonth::latest()->get();
        return successResponse(PaymentMonthResource::collection($months));
    }

    public function store(PaymentMonthRequest $request)
    {
        try {
            $month = DB::transaction(function () use ($request) {
                return PaymentMonth::create($request->validated());
            });

            return successResponse(new PaymentMonthResource($month), 'Payment month created successfully', 201);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }

    public function show(PaymentMonth $paymentMonth)
    {
        return successResponse(new PaymentMonthResource($paymentMonth));
    }

    public function update(PaymentMonthRequest $request, PaymentMonth $paymentMonth)
    {
        try {
            $month = DB::transaction(function () use ($request, $paymentMonth) {
                $paymentMonth->update($request->validated());
                return $paymentMonth;
            });

            return successResponse(new PaymentMonthResource($month), 'Payment month updated successfully');
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(PaymentMonth $paymentMonth)
    {
        try {
            DB::transaction(function () use ($paymentMonth) {
                $paymentMonth->delete();
            });

            return successResponse(null, 'Payment month deleted successfully', 204);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }
}
