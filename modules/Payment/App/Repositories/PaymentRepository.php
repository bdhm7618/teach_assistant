<?php

namespace Modules\Payment\App\Repositories;

use Modules\Payment\App\Models\Payment;
use Modules\Payment\App\Enums\PaymentStatus;
use Modules\Payment\App\Enums\PaymentMethod;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentRepository extends BaseRepository
{
    public function model()
    {
        return Payment::class;
    }

    public function create(array $data): Payment
    {
        if (!isset($data['payment_date'])) {
            $data['payment_date'] = Carbon::now();
        }

        if (!isset($data['final_amount'])) {
            $data['final_amount'] = ($data['amount'] ?? 0) - ($data['discount_amount'] ?? 0);
        }

        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        if (!isset($data['paid_by']) && auth('user')->check()) {
            $data['paid_by'] = auth('user')->id();
        }

        // Auto-assign payment period if not provided and payment_date is set
        if (!isset($data['payment_period_id']) && isset($data['payment_date'])) {
            $paymentDate = Carbon::parse($data['payment_date']);
            $periodRepo = app(\Modules\Payment\App\Repositories\PaymentPeriodRepository::class);
            $currentPeriod = $periodRepo->getCurrentPeriod($data['channel_id'] ?? null);
            
            if ($currentPeriod && $currentPeriod->containsDate($paymentDate)) {
                $data['payment_period_id'] = $currentPeriod->id;
            }
        }

        return $this->model->create($data);
    }

    public function update(array $data, $id): Payment
    {
        $payment = $this->model->findOrFail($id);
        
        if (isset($data['amount']) || isset($data['discount_amount'])) {
            $amount = $data['amount'] ?? $payment->amount;
            $discount = $data['discount_amount'] ?? $payment->discount_amount;
            $data['final_amount'] = $amount - $discount;
        }

        $payment->update($data);
        return $payment->fresh();
    }

    public function delete($id): bool
    {
        $payment = $this->model->findOrFail($id);
        return $payment->delete();
    }

    /**
     * Get payments by student
     */
    public function getByStudent(int $studentId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = $this->model->where('student_id', $studentId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /**
     * Get payments by group
     */
    public function getByGroup(int $groupId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = $this->model->where('group_id', $groupId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /**
     * Get financial statistics
     */
    public function getFinancialStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->model->completed();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $totalRevenue = $query->sum('final_amount');
        $totalPayments = $query->count();
        $averagePayment = $totalPayments > 0 ? $totalRevenue / $totalPayments : 0;

        $byMethod = $query->clone()
            ->select('payment_method', DB::raw('SUM(final_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => [
                    'total' => $item->total,
                    'count' => $item->count,
                ]];
            });

        return [
            'total_revenue' => $totalRevenue,
            'total_payments' => $totalPayments,
            'average_payment' => round($averagePayment, 2),
            'by_method' => $byMethod,
        ];
    }

    /**
     * Get student payment summary
     */
    public function getStudentSummary(int $studentId): array
    {
        $totalPaid = $this->model->where('student_id', $studentId)
            ->completed()
            ->sum('final_amount');

        $totalPending = $this->model->where('student_id', $studentId)
            ->pending()
            ->sum('final_amount');

        $totalPayments = $this->model->where('student_id', $studentId)
            ->completed()
            ->count();

        return [
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'total_payments' => $totalPayments,
        ];
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(int $id, ?string $transactionId = null): Payment
    {
        $payment = $this->model->findOrFail($id);
        $payment->status = PaymentStatus::COMPLETED;
        
        if ($transactionId) {
            $payment->transaction_id = $transactionId;
        }

        $payment->save();

        // Update invoice if exists
        if ($payment->invoice_id) {
            $payment->invoice->updatePaymentStatus();
        }

        // Update installment if exists
        if ($payment->installment_id) {
            $installment = $payment->installment;
            $paidAmount = $installment->getPaidAmount();
            if ($paidAmount >= $installment->amount) {
                $installment->status = 'paid';
                $installment->paid_date = Carbon::now();
                $installment->save();
            }
        }

        return $payment->fresh();
    }

    /**
     * Refund payment
     */
    public function refund(int $id, ?string $notes = null): Payment
    {
        $payment = $this->model->findOrFail($id);

        if (!$payment->canBeRefunded()) {
            throw new \Exception('Payment cannot be refunded');
        }

        $payment->status = PaymentStatus::REFUNDED;
        if ($notes) {
            $payment->notes = ($payment->notes ? $payment->notes . "\n" : '') . "Refund: " . $notes;
        }
        $payment->save();

        // Update invoice if exists
        if ($payment->invoice_id) {
            $payment->invoice->updatePaymentStatus();
        }

        return $payment->fresh();
    }
}

