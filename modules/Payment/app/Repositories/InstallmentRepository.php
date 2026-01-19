<?php

namespace Modules\Payment\App\Repositories;

use Modules\Payment\App\Models\Installment;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

class InstallmentRepository extends BaseRepository
{
    public function model()
    {
        return Installment::class;
    }

    public function create(array $data): Installment
    {
        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        return $this->model->create($data);
    }

    public function update(array $data, $id): Installment
    {
        $installment = $this->model->findOrFail($id);
        $installment->update($data);
        return $installment->fresh();
    }

    public function delete($id): bool
    {
        $installment = $this->model->findOrFail($id);
        return $installment->delete();
    }

    /**
     * Get installments by invoice
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->model->where('invoice_id', $invoiceId)
            ->orderBy('installment_number', 'asc')
            ->get();
    }

    /**
     * Get overdue installments
     */
    public function getOverdue(): Collection
    {
        return $this->model->where('status', '!=', 'paid')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date', 'asc')
            ->get();
    }
}

