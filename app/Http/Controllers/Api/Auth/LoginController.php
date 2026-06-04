<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return $this->success(
            new UserResource($user),
            'Data user berhasil diambil'
        );
    }
}
