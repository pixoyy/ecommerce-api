<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    public function show(): JsonResponse
    {
        return $this->success(
            new UserResource(auth()->user()->load('userPoints')),
            'Data profil berhasil diambil'
        );
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(
            auth()->id(),
            $request->validated()
        );

        return $this->success(
            new UserResource($user->load('userPoints')),
            'Profil berhasil diupdate'
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword(
                auth()->id(),
                $request->current_password,
                $request->new_password
            );

            return $this->success(null, 'Password berhasil diubah');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
