<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logout berhasil');
    }
}
