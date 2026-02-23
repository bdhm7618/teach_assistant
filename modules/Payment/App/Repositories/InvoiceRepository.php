<?php

namespace Modules\Payment\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Payment\App\Models\Invoice;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Payment\App\Repositories\InstallmentRepository;

class InvoiceRepository extends BaseRepository
{
    public function model()
    {
        return Invoice::class;
    }

    public function create(array $data): Invoice
    {
        if (!isset($data['invoice_number'])) {
            $channelId = $data['channel_id'] ?? auth('user')->user()?->channel_id;
            $data['invoice_number'] = Invoice::generateInvoiceNumber($channelId);
        }

        if (!isset($data['issue_date'])) {
            $data['issue_date'] = Carbon::today();
        }

        if (!isset($data['final_amount'])) {
            $data['final_amount'] = ($data['total_amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        }

        if (!isset($data['remaining_amount'])) {
            $data['remaining_amount'] = $data['final_amount'];
        }

        if (!isset($data['paid_amount'])) {
            $data['paid_amount'] = 0;
        }

        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        return $this->model->create($data);
    }

    public function update(array $data, $id): Invoice
    {
        $invoice = $this->model->findOrFail($id);
        
        if (isset($data['total_amount']) || isset($data['discount_amount'])) {
            $total = $data['total_amount'] ?? $invoice->total_amount;
            $discount = $data['discount_amount'] ?? $invoice->discount_amount;
            $data['final_amount'] = $total - $discount;
            $data['remaining_amount'] = $data['final_amount'] - $invoice->paid_amount;
        }

        $invoice->update($data);
        return $invoice->fresh();
    }

    public function delete($id): bool
    {
        $invoice = $this->model->findOrFail($id);
        return $invoice->delete();
    }

    /**
     * Create invoice with installments
     */
    public function createWithInstallments(array $invoiceData, array $installmentsData): Invoice
    {
        DB::beginTransaction();
        try {
            $invoice = $this->create($invoiceData);

            $installmentRepo = app(\Modules\Payment\App\Repositories\InstallmentRepository::class);
            
            foreach ($installmentsData as $index => $installmentData) {
                $installmentData['invoice_id'] = $invoice->id;
                $installmentData['installment_number'] = $index + 1;
                $installmentData['channel_id'] = $invoice->channel_id;
                $installmentRepo->create($installmentData);
            }

            DB::commit();
            return $invoice->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get invoices by student
     */
    public function getByStudent(int $studentId): Collection
    {
        return $this->model->where('student_id', $studentId)
            ->orderBy('issue_date', 'desc')
            ->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(): Collection
    {
        return $this->model->where('status', 'overdue')
            ->orWhere(function ($query) {
                $query->where('status', 'pending')
                    ->where('due_date', '<', Carbon::today());
            })
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get pending invoices
     */
    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')
            ->where('due_date', '>=', Carbon::today())
            ->orderBy('due_date', 'asc')
            ->get();
    }
}

