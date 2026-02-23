<?php

namespace Modules\Payment\App\Repositories;

use Modules\Payment\App\Models\Discount;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Support\Collection;

class DiscountRepository extends BaseRepository
{
    public function model()
    {
        return Discount::class;
    }

    public function create(array $data): Discount
    {
        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        if (!isset($data['code'])) {
            $data['code'] = $this->generateUniqueCode($data['channel_id'] ?? null);
        }

        return $this->model->create($data);
    }

    public function update(array $data, $id): Discount
    {
        $discount = $this->model->findOrFail($id);
        $discount->update($data);
        return $discount->fresh();
    }

    public function delete($id): bool
    {
        $discount = $this->model->findOrFail($id);
        return $discount->delete();
    }

    /**
     * Find discount by code
     */
    public function findByCode(string $code, ?int $channelId = null): ?Discount
    {
        $query = $this->model->where('code', $code);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->first();
    }

    /**
     * Get active discounts
     */
    public function getActive(?int $channelId = null): Collection
    {
        $query = $this->model->where('is_active', true);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        return $query->get()->filter(function ($discount) {
            return $discount->isValid();
        });
    }

    /**
     * Generate unique discount code
     */
    protected function generateUniqueCode(?int $channelId = null): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while ($this->model->where('code', $code)->exists());

        return $code;
    }

    /**
     * Apply discount to amount
     */
    public function applyDiscount(string $code, float $amount, ?int $channelId = null): array
    {
        $discount = $this->findByCode($code, $channelId);

        if (!$discount || !$discount->isValid()) {
            return [
                'success' => false,
                'message' => trans('payment::app.discount.invalid'),
                'discount_amount' => 0,
                'final_amount' => $amount,
            ];
        }

        if ($discount->min_amount && $amount < $discount->min_amount) {
            return [
                'success' => false,
                'message' => trans('payment::app.discount.min_amount', ['amount' => $discount->min_amount]),
                'discount_amount' => 0,
                'final_amount' => $amount,
            ];
        }

        $discountAmount = $discount->calculateDiscount($amount);
        $finalAmount = $amount - $discountAmount;

        return [
            'success' => true,
            'message' => trans('payment::app.discount.applied'),
            'discount' => $discount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }
}

