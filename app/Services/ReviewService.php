<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function createReview(int $userId, int $orderId, array $data): Review
    {
        return DB::transaction(function () use ($userId, $orderId, $data) {
            $order = Order::where('id', $orderId)->where('user_id', $userId)->firstOrFail();

            if ($order->status !== Order::STATUS_DELIVERED) {
                throw new \Exception('Ulasan hanya dapat diberikan untuk pesanan yang sudah selesai');
            }

            $orderItem = OrderItem::where('order_id', $orderId)
                ->where('product_variant_id', $data['product_variant_id'])
                ->first();

            if (!$orderItem) {
                throw new \Exception('Varian produk tidak ditemukan di pesanan ini');
            }

            $existing = Review::where('user_id', $userId)
                ->where('order_item_id', $orderItem->id)
                ->exists();

            if ($existing) {
                throw new \Exception('Anda sudah memberikan ulasan untuk produk ini');
            }

            $variant = ProductVariant::findOrFail($data['product_variant_id']);

            return Review::create([
                'user_id' => $userId,
                'product_id' => $variant->product_id,
                'order_item_id' => $orderItem->id,
                'rating' => $data['rating'],
                'reason' => $data['review'] ?? null,
                'is_visible' => true,
            ]);
        });
    }
}
