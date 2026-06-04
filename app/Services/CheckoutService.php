<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\UserPoint;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(int $userId, array $data): Order
    {
        return DB::transaction(function () use ($userId, $data) {
            $cartItems = Cart::where('user_id', $userId)
                ->with([
                    'productVariant.product',
                    'productVariant.promotionItems.promotion',
                    'productVariant.warehouseStocks',
                ])
                ->get();

            throw_if($cartItems->isEmpty(), \Exception::class, 'Keranjang belanja kosong');

            foreach ($cartItems as $item) {
                $variant = $item->productVariant;

                throw_if(!$variant->is_active, \Exception::class, "Variant {$variant->label} tidak aktif");

                $stocks = WarehouseStock::where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->get();

                $totalStock = $stocks->sum('quantity');
                throw_if($totalStock < $item->quantity, \Exception::class, "Stok {$variant->label} tidak mencukupi");
            }

            $orderItems = [];
            $subtotal = 0;

            foreach ($cartItems as $item) {
                $variant = $item->productVariant;

                $activePromo = $variant->promotionItems->first(function ($pi) {
                    return $pi->promotion
                        && $pi->promotion->is_active
                        && now()->between($pi->promotion->start_at, $pi->promotion->end_at);
                });

                $unitPrice = $activePromo
                    ? (float) $activePromo->override_price
                    : (float) $variant->price;

                $itemSubtotal = $unitPrice * $item->quantity;
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'promotion_id' => $activePromo?->promotion_id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $shippingCost = 15000;

            $pointRedeemed = 0;
            $pointEarned = 0;

            if (!empty($data['redeem_points']) && $data['redeem_points'] > 0) {
                $userPoints = UserPoint::where('user_id', $userId)->lockForUpdate()->first();
                $maxRedeem = min($data['redeem_points'], $userPoints?->balance ?? 0, $subtotal);

                if ($maxRedeem > 0) {
                    $pointRedeemed = $maxRedeem;
                    $userPoints->decrement('balance', $pointRedeemed);

                    PointTransaction::create([
                        'user_id' => $userId,
                        'type' => 2,
                        'amount' => $pointRedeemed,
                        'description' => 'Redeem poin untuk pesanan',
                    ]);
                }
            }

            $total = $subtotal + $shippingCost - $pointRedeemed;
            throw_if($total < 0, \Exception::class, 'Total pesanan tidak valid');

            $orderNumber = 'INV/' . now()->format('Ymd') . '/' . str_pad(
                Order::whereDate('created_at', today())->count() + 1, 5, '0', STR_PAD_LEFT
            );

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'point_redeemed' => $pointRedeemed,
                'point_earned' => $pointEarned,
                'total' => $total,
                'buyer_name' => $data['buyer_name'] ?? auth()->user()->name,
                'buyer_email' => $data['buyer_email'] ?? auth()->user()->email,
                'buyer_phone' => $data['buyer_phone'] ?? auth()->user()->phone,
                'shipping_address' => $data['shipping_address'],
                'shipping_note' => $data['shipping_note'] ?? null,
                'note' => null,
            ]);

            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            foreach ($cartItems as $item) {
                $remainingQty = $item->quantity;
                $stocks = WarehouseStock::where('product_variant_id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->orderBy('quantity', 'desc')
                    ->get();

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $deduct = min($stock->quantity, $remainingQty);
                    $stock->decrement('quantity', $deduct);
                    $remainingQty -= $deduct;
                }
            }

            Cart::where('user_id', $userId)->delete();

            $order->load(['orderItems', 'user']);

            return $order;
        });
    }
}
