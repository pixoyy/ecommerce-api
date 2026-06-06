<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;

/**
 * @group Authentication
 *
 * Customer registration.
 */
class RegisterController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register
     *
     * Create a new customer account and return a Bearer token.
     *
     * @bodyParam name string required Full name. Example: Budi Santoso
     * @bodyParam email string required Email address. Example: budi@example.com
     * @bodyParam phone string Phone number. Example: 08123456789
     * @bodyParam password string required Password (min 8 chars). Example: password123
     * @bodyParam password_confirmation string required Confirm password. Example: password123
     *
     * @unauthenticated
     */
    public function __invoke(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());
        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Registrasi berhasil', 201);
    }
}
