<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartItemRequest;
use App\Http\Requests\Api\CartUpdateRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(): JsonResponse
    {
        $cartItems = $this->cartService->getCart(auth()->id());

        return $this->success(
            CartResource::collection($cartItems),
            'Keranjang berhasil diambil'
        );
    }

    public function store(CartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem(
            auth()->id(),
            $request->product_variant_id,
            $request->quantity
        );

        return $this->success(
            new CartResource($cart),
            'Item berhasil ditambahkan ke keranjang',
            201
        );
    }

    public function update(CartUpdateRequest $request, Cart $item): JsonResponse
    {
        if ($item->user_id !== auth()->id()) {
            return $this->error('Item tidak ditemukan', 404);
        }

        $cart = $this->cartService->updateItemQuantity(
            auth()->id(),
            $item->id,
            $request->quantity
        );

        return $this->success(
            new CartResource($cart),
            'Quantity berhasil diupdate'
        );
    }

    public function destroy(Cart $item): JsonResponse
    {
        if ($item->user_id !== auth()->id()) {
            return $this->error('Item tidak ditemukan', 404);
        }

        $this->cartService->removeItem(auth()->id(), $item->id);

        return $this->success(null, 'Item berhasil dihapus dari keranjang');
    }
}
