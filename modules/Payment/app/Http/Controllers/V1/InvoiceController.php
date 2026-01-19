<?php

namespace Modules\Payment\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Payment\App\Repositories\InvoiceRepository;
use Modules\Payment\App\Http\Requests\V1\InvoiceRequest;
use Modules\Payment\App\Http\Resources\V1\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;

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

    public function store(InvoiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $invoice = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new InvoiceResource($invoice),
                trans('payment::app.invoice.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function createWithInstallments(InvoiceRequest $request)
    {
        $request->validate([
            'installments' => 'required|array|min:1',
            'installments.*.amount' => 'required|numeric|min:0.01',
            'installments.*.due_date' => 'required|date|after_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            $invoiceData = $request->except('installments');
            $installmentsData = $request->input('installments', []);

            $invoice = $this->repository->createWithInstallments($invoiceData, $installmentsData);
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

    public function update(InvoiceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $invoice = $this->repository->update($request->validated(), $id);
            DB::commit();
            return successResponse(
                new InvoiceResource($invoice),
                trans('payment::app.invoice.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.invoice.not_found'),
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
            $invoice = $this->repository->findOrFail($id);
            $this->repository->delete($invoice->id);
            DB::commit();
            return successResponse(null, trans('payment::app.invoice.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('payment::app.invoice.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getByStudent($studentId)
    {
        try {
            $invoices = $this->repository->getByStudent($studentId);
            return successResponse(
                InvoiceResource::collection($invoices),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getOverdue()
    {
        try {
            $invoices = $this->repository->getOverdue();
            return successResponse(
                InvoiceResource::collection($invoices),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }

    public function getPending()
    {
        try {
            $invoices = $this->repository->getPending();
            return successResponse(
                InvoiceResource::collection($invoices),
                trans('payment::app.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('payment::app.operation_failed'), $e);
        }
    }
}

