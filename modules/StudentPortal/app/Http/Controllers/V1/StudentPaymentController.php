<?php

namespace Modules\StudentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Payment\App\Models\Invoice;
use Modules\StudentPortal\App\Http\Resources\V1\InvoiceResource;

/**
 * @OA\Tag(name="Student Payments", description="Student portal — invoice history and payment records")
 */
class StudentPaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/invoices",
     *     summary="List student's invoices",
     *     tags={"Student Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="pending|paid|overdue|all", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Invoice list")
     * )
     */
    public function invoices(Request $request): JsonResponse
    {
        $student = auth('student')->user();
        $perPage = (int) $request->input('per_page', 15);
        $status  = $request->input('status');

        $query = Invoice::where('student_id', $student->id)
            ->with('group')
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByDesc('issue_date');

        $invoices = $query->paginate($perPage);

        return successResponse(
            InvoiceResource::collection($invoices)->response()->getData(true),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/invoices/{invoice_id}",
     *     summary="Get invoice detail with payment history",
     *     tags={"Student Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="invoice_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Invoice with payments"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showInvoice(int $invoiceId): JsonResponse
    {
        $student = auth('student')->user();

        $invoice = Invoice::where('student_id', $student->id)
            ->with(['group', 'payments'])
            ->findOrFail($invoiceId);

        return successResponse(
            new InvoiceResource($invoice),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/invoices/summary",
     *     summary="Get payment summary (total, paid, outstanding)",
     *     tags={"Student Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Payment summary")
     * )
     */
    public function summary(): JsonResponse
    {
        $student = auth('student')->user();

        $invoices = Invoice::where('student_id', $student->id)->get();

        $summary = [
            'total_invoiced'  => $invoices->sum('final_amount'),
            'total_paid'      => $invoices->sum('paid_amount'),
            'total_remaining' => $invoices->sum('remaining_amount'),
            'pending_count'   => $invoices->where('status', 'pending')->count(),
            'overdue_count'   => $invoices->where('status', 'overdue')->count(),
            'paid_count'      => $invoices->where('status', 'paid')->count(),
        ];

        return successResponse($summary, __('studentportal::app.show_success'));
    }
}
