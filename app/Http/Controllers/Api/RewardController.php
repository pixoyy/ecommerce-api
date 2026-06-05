<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointTransactionResource;
use App\Services\RewardPointService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected RewardPointService $rewardPointService
    ) {}

    public function balance(): JsonResponse
    {
        $userPoints = $this->rewardPointService->getBalance(auth()->id());

        return $this->success([
            'balance' => (int) $userPoints->balance,
        ], 'Saldo poin berhasil diambil');
    }

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
