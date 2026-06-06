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

/**
 * @group Cart
 *
 * Shopping cart management for authenticated users.
 *
 * @authenticated
 */
class CartController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * List cart items
     *
     * Get all items in the authenticated user's cart.
     */
    public function index(): JsonResponse
    {
        $cartItems = $this->cartService->getCart(auth()->id());

        return $this->success(
            CartResource::collection($cartItems),
            'Keranjang berhasil diambil'
        );
    }

    /**
     * Add item to cart
     *
     * Add a product variant to cart. If the variant already exists, quantity will be incremented.
     *
     * @bodyParam product_variant_id integer required The variant ID. Example: 1
     * @bodyParam quantity integer required Quantity to add. Example: 2
     */
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

    /**
     * Update cart item quantity
     *
     * @urlParam item integer required The cart item ID. Example: 1
     * @bodyParam quantity integer required New quantity. Example: 3
     */
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

    /**
     * Remove item from cart
     *
     * @urlParam item integer required The cart item ID. Example: 1
     */
    public function destroy(Cart $item): JsonResponse
    {
        if ($item->user_id !== auth()->id()) {
            return $this->error('Item tidak ditemukan', 404);
        }

        $this->cartService->removeItem(auth()->id(), $item->id);

        return $this->success(null, 'Item berhasil dihapus dari keranjang');
    }
}
