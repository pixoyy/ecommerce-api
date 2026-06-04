<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    private array $with = [
        'productVariant.product.thumbnailImage',
        'productVariant.promotionItems.promotion',
        'productVariant.warehouseStocks',
    ];

    public function getCart(int $userId): Collection
    {
        return Cart::where('user_id', $userId)
            ->with($this->with)
            ->get();
    }

    public function addItem(int $userId, int $variantId, int $quantity): Cart
    {
        $variant = ProductVariant::with('warehouseStocks')->findOrFail($variantId);

        if (!$variant->is_active) {
            abort(422, 'Varian produk tidak aktif');
        }

        $totalStock = $variant->warehouseStocks->sum('quantity');
        if ($totalStock < $quantity) {
            abort(422, 'Stok tidak mencukupi');
        }

        $cart = Cart::updateOrCreate(
            ['user_id' => $userId, 'product_variant_id' => $variantId],
            ['quantity' => DB::raw("quantity + {$quantity}")]
        );

        $cart->load($this->with);

        return $cart;
    }

    public function updateItemQuantity(int $userId, int $cartId, int $quantity): Cart
    {
        $cart = Cart::where('id', $cartId)
            ->where('user_id', $userId)
            ->with($this->with)
            ->firstOrFail();

        $totalStock = $cart->productVariant->warehouseStocks->sum('quantity');
        if ($totalStock < $quantity) {
            abort(422, 'Stok tidak mencukupi');
        }

        $cart->update(['quantity' => $quantity]);
        $cart->load($this->with);

        return $cart;
    }

    public function removeItem(int $userId, int $cartId): void
    {
        Cart::where('id', $cartId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function clearCart(int $userId): void
    {
        Cart::where('user_id', $userId)->delete();
    }
}
