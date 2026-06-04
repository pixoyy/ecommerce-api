<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    public function getActiveAccounts(): Collection
    {
        return PaymentAccount::where('is_active', 1)->get();
    }

    public function uploadPayment(int $userId, int $orderId, UploadedFile $proof, float $amount): Payment
    {
        return DB::transaction(function () use ($userId, $orderId, $proof, $amount) {
            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            throw_if($order->status !== Order::STATUS_PENDING, \Exception::class, 'Pesanan sudah tidak bisa dibayar');

            $pendingPayment = Payment::where('order_id', $orderId)
                ->where('status', 1)
                ->exists();

            throw_if($pendingPayment, \Exception::class, 'Sudah ada pembayaran yang menunggu konfirmasi');

            throw_if(
                (float) $amount !== (float) $order->total,
                \Exception::class,
                'Jumlah pembayaran harus sama dengan total pesanan'
            );

            $fileStorage = $this->fileStorageService->upload($proof, 'uploads/payments');

            return Payment::create([
                'order_id' => $orderId,
                'proof_path' => $fileStorage->id,
                'amount' => $amount,
                'status' => 1,
            ]);
        });
    }
}
