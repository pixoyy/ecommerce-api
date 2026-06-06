<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointTransactionResource;
use App\Services\RewardPointService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Rewards
 *
 * Loyalty points balance and transaction history.
 *
 * @authenticated
 */
class RewardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected RewardPointService $rewardPointService
    ) {}

    /**
     * Get point balance
     *
     * Show the authenticated user's loyalty points balance.
     */
    public function balance(): JsonResponse
    {
        $userPoints = $this->rewardPointService->getBalance(auth()->id());

        return $this->success([
            'balance' => (int) $userPoints->balance,
        ], 'Saldo poin berhasil diambil');
    }

    /**
     * List point transactions
     *
     * Get paginated history of point earnings and redemptions.
     *
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->rewardPointService->getTransactions(
            auth()->id(),
            $request->only(['per_page'])
        );

        return $this->success(
            PointTransactionResource::collection($transactions),
            'Riwayat poin berhasil diambil'
        );
    }
}
