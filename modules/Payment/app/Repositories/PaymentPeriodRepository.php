<?php

namespace Modules\Payment\App\Repositories;

use Modules\Payment\App\Models\PaymentPeriod;
use Modules\Payment\App\Enums\PaymentPeriodType;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PaymentPeriodRepository extends BaseRepository
{
    public function model()
    {
        return PaymentPeriod::class;
    }

    public function create(array $data): PaymentPeriod
    {
        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        // Auto-generate name if not provided
        if (!isset($data['name']) && isset($data['period_type'])) {
            $type = PaymentPeriodType::from($data['period_type']);
            $data['name'] = PaymentPeriod::generateName(
                $type,
                $data['month'] ?? null,
                $data['year'] ?? null,
                isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
                isset($data['end_date']) ? Carbon::parse($data['end_date']) : null
            );
        }

        return $this->model->create($data);
    }

    public function update(array $data, $id): PaymentPeriod
    {
        $period = $this->model->findOrFail($id);
        $period->update($data);
        return $period->fresh();
    }

    public function delete($id): bool
    {
        $period = $this->model->findOrFail($id);
        return $period->delete();
    }

    /**
     * Get or create monthly period
     */
    public function getOrCreateMonthly(int $year, int $month, ?int $channelId = null): PaymentPeriod
    {
        $channelId = $channelId ?? auth('user')->user()?->channel_id;

        $period = $this->model->where('channel_id', $channelId)
            ->where('period_type', PaymentPeriodType::MONTHLY->value)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (!$period) {
            $period = PaymentPeriod::createMonthly($year, $month, $channelId);
        }

        return $period;
    }

    /**
     * Get or create weekly period
     */
    public function getOrCreateWeekly(Carbon $startDate, Carbon $endDate, ?int $channelId = null): PaymentPeriod
    {
        $channelId = $channelId ?? auth('user')->user()?->channel_id;

        $period = $this->model->where('channel_id', $channelId)
            ->where('period_type', PaymentPeriodType::WEEKLY->value)
            ->where('start_date', $startDate->toDateString())
            ->where('end_date', $endDate->toDateString())
            ->first();

        if (!$period) {
            $period = PaymentPeriod::createWeekly($startDate, $endDate, $channelId);
        }

        return $period;
    }

    /**
     * Get open periods
     */
    public function getOpenPeriods(?int $channelId = null): Collection
    {
        $query = $this->model->where('is_open', true)
            ->where('is_active', true);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * Get periods by type
     */
    public function getByType(PaymentPeriodType $type, ?int $channelId = null): Collection
    {
        $query = $this->model->where('period_type', $type->value);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * Get current period
     */
    public function getCurrentPeriod(?int $channelId = null): ?PaymentPeriod
    {
        $channelId = $channelId ?? auth('user')->user()?->channel_id;
        $today = Carbon::today();

        return $this->model->where('channel_id', $channelId)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Get period statistics
     */
    public function getPeriodStatistics(int $periodId): array
    {
        $period = $this->model->findOrFail($periodId);

        return [
            'period' => $period,
            'total_payments' => $period->getPaymentsCount(),
            'total_amount' => $period->getTotalPayments(),
            'by_group' => $period->payments()
                ->select('group_id', \DB::raw('SUM(final_amount) as total'), \DB::raw('COUNT(*) as count'))
                ->where('status', \Modules\Payment\App\Enums\PaymentStatus::COMPLETED->value)
                ->groupBy('group_id')
                ->get(),
        ];
    }
}

