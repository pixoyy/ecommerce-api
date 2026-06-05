<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\UserPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RewardPointService
{
    public function getBalance(int $userId): UserPoint
    {
        return UserPoint::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    public function getTransactions(int $userId, array $filters = []): LengthAwarePaginator
    {
        return PointTransaction::where('user_id', $userId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
