<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\InvoiceRepository;
use Modules\Payment\App\Http\Requests\V1\InvoiceRequest;
use Modules\Payment\App\Http\Resources\V1\InvoiceResource;

/**
 * @OA\Tag(name="Invoices", description="Invoice management — ad-hoc dues, monthly billing, installments")
 */
class InvoiceController extends BaseController
{
    protected InvoiceRepository $repository;

    public function __construct(InvoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return InvoiceResource::class;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/invoices",
     *     summary="Create an invoice (monthly, ad-hoc, session, or enrollment fee)",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","total_amount","due_date"},
     *             @OA\Property(property="student_id",     type="integer"),
     *             @OA\Property(property="group_id",       type="integer", nullable=true),
     *             @OA\Property(property="enrollment_id",  type="integer", nullable=true),
     *             @OA\Property(property="total_amount",   type="number",  example=500.00),
     *             @OA\Property(property="discount_amount",type="number",  nullable=true),
     *             @OA\Property(property="due_date",       type="string",  format="date"),
     *             @OA\Property(property="issue_date",     type="string",  format="date", nullable=true),
     *             @OA\Property(property="type",           type="string",  enum={"monthly","session","enrollment_fee","ad_hoc"}, nullable=true),
     *             @OA\Property(property="reason",         type="string",  nullable=true, description="Required when type=ad_hoc"),
     *             @OA\Property(property="notes",          type="string",  nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created"),
     *     @OA\Response(response=403, description="Requires payments.create"),
     *     @OA\Response(response=422, description="Validation error — reason required for ad_hoc type")
     * )
     */
    public function store(InvoiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $invoice = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(new InvoiceResource($invoice), trans('payment::app.invoice.created'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/invoices/with-installments",
     *     summary="Create an invoice and split it into installments",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","total_amount","due_date","installments"},
     *             @OA\Property(property="student_id",   type="integer"),
     *             @OA\Property(property="total_amount", type="number"),
     *             @OA\Property(property="due_date",     type="string", format="date"),
     *             @OA\Property(property="installments", type="array", @OA\Items(
     *                 @OA\Property(property="amount",   type="number"),
     *                 @OA\Property(property="due_date", type="string", format="date")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created with installments"),
     *     @OA\Response(response=403, description="Requires payments.create"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createWithInstallments(InvoiceRequest $request)
    {
        $request->validate([
            'installments'           => 'required|array|min:1',
            'installments.*.amount'  => 'required|numeric|min:0.01',
            'installments.*.due_date'=> 'required|date|after_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            $invoice = $this->repository->createWithInstallments(
                $request->except('installments'),
                $request->input('installments', [])
            );
            DB::commit();
            return successResponse(
                new InvoiceResource($invoice->load('installments')),
                trans('payment::app.invoice.created_with_installments'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/invoices/{id}",
     *     summary="Update an invoice",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="total_amount",    type="number"),
     *         @OA\Property(property="discount_amount", type="number", nullable=true),
     *         @OA\Property(property="due_date",        type="string", format="date"),
     *         @OA\Property(property="notes",           type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=403, description="Requires payments.update"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(InvoiceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $invoice = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(new InvoiceResource($invoice), trans('payment::app.invoice.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.invoice.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/invoices/{id}",
     *     summary="Delete an invoice",
     *     tags={"Invoices"},
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
            $invoice = $this->repository->findOrFail($id);
            $this->repository->delete($invoice->id);
            DB::commit();
            return successResponse(null, trans('payment::app.invoice.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.invoice.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/invoices/student/{studentId}",
     *     summary="List invoices for a student",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="studentId",    in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Invoice list"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getByStudent($studentId)
    {
        try {
            $invoices = $this->repository->getByStudent($studentId);
            return successResponse(InvoiceResource::collection($invoices), trans('payment::app.list_success'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/invoices/overdue",
     *     summary="List all overdue invoices (payment dues)",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Overdue invoice list — sorted by due_date asc"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getOverdue()
    {
        try {
            $invoices = $this->repository->getOverdue();
            return successResponse(InvoiceResource::collection($invoices), trans('payment::app.list_success'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/invoices/pending",
     *     summary="List pending invoices not yet due",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Pending invoice list"),
     *     @OA\Response(response=403, description="Requires payments.view")
     * )
     */
    public function getPending()
    {
        try {
            $invoices = $this->repository->getPending();
            return successResponse(InvoiceResource::collection($invoices), trans('payment::app.list_success'));
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }
}
