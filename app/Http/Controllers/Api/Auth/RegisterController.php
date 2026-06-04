<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;

class RegisterController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

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
