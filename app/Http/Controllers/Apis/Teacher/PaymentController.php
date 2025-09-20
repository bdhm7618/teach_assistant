<?php

namespace App\Http\Controllers\Apis\Teacher;

use App\Models\Payment;
use App\Models\PaymentMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\PaymentRequest;
use App\Http\Resources\Payment\PaymentResource;

class PaymentController extends Controller
{

    public function index(Request $request)
    {
        $query = Payment::query()->with(['student', 'teacher', 'paymentMonth']);


        if ($request->has('payment_month_id')) {
            $query->where('payment_month_id', $request->input('payment_month_id'));
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }
        if ($request->has('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        $payments = $query->latest()->get();

        return successResponse(PaymentResource::collection($payments));
    }


    public function show(Payment $payment)
    {
        $payment->load(['student', 'teacher', 'paymentMonth']);
        return successResponse(new PaymentResource($payment));
    }

    public function store(PaymentRequest $request)
    {
        $data = $request->validated();

    
        if (! PaymentMonth::isOpen($data['payment_month_id'])) {
            return errorResponse("error", " ", "Payments for this month are closed.", 422);
        }

        $is_paid = Payment::where("student_id", $data["student_id"])
            ->where("payment_month_id", $data["payment_month_id"])->exists();

        if ($is_paid) {
            return response([
                "warning" => "This student has already paid for this month."
            ], 422);
        }


        $data["teacher_id"] = $this->teacher->id;
        $data["paid_at"] = date("Y-m-d H:i:s");
        $data["status"] = 1;

        try {
            $payment = DB::transaction(function () use ($data) {
                return Payment::create($data);
            });

            return successResponse(new PaymentResource($payment), 'Payment created', 201);
        } catch (\Throwable $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }

    // PUT/PATCH /api/payments/{payment}
    public function update(PaymentRequest $request, Payment $payment)
    {
        $data = $request->validated();

        if (isset($data['payment_month_id']) && ! PaymentMonth::isOpen($data['payment_month_id'])) {
            return errorResponse("Payments for the selected month are closed.", 422);
        }

        try {
            $updated = DB::transaction(function () use ($payment, $data) {
                $payment->update($data);
                return $payment->refresh();
            });

            return successResponse(new PaymentResource($updated), 'Payment updated');
        } catch (\Throwable $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }

    // DELETE /api/payments/{payment}
    public function destroy(Payment $payment)
    {
        try {
            DB::transaction(function () use ($payment) {
                $payment->delete();
            });

            return successResponse(null, 'Payment deleted', 204);
        } catch (\Throwable $e) {
            return errorResponse($e->getMessage(), 422);
        }
    }
}
