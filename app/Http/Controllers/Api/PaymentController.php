<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaymentUploadRequest;
use App\Http\Resources\PaymentAccountResource;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @group Payment
 *
 * Payment accounts and proof upload.
 *
 * @authenticated
 */
class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * List payment accounts
     *
     * Get active bank accounts for manual transfer.
     */
    public function accounts(): JsonResponse
    {
        $accounts = $this->paymentService->getActiveAccounts();

        return $this->success(
            PaymentAccountResource::collection($accounts),
            'Daftar rekening pembayaran berhasil diambil'
        );
    }

    /**
     * Upload payment proof
     *
     * Upload payment proof image for a pending order.
     *
     * @urlParam order integer required The order ID. Example: 1
     * @bodyParam proof file required Payment proof image (jpg/jpeg/png, max 2MB).
     * @bodyParam amount numeric required Transfer amount. Must match order total. Example: 65000
     */
    public function upload(Order $order, PaymentUploadRequest $request): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return $this->error('Pesanan tidak ditemukan', 404);
        }

        try {
            $payment = $this->paymentService->uploadPayment(
                auth()->id(),
                $order->id,
                $request->file('proof'),
                (float) $request->amount
            );

            return $this->success(
                new PaymentResource($payment->load('proofPath')),
                'Bukti pembayaran berhasil diupload',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
