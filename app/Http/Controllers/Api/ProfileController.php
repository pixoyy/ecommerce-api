<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @group Profile
 *
 * Customer profile management.
 *
 * @authenticated
 */
class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Get profile
     *
     * Show the authenticated user's profile data.
     */
    public function show(): JsonResponse
    {
        return $this->success(
            new UserResource(auth()->user()->load('userPoints')),
            'Data profil berhasil diambil'
        );
    }

    /**
     * Update profile
     *
     * Update name, phone, or email of the authenticated user.
     *
     * @bodyParam name string required Full name. Example: Budi Santoso
     * @bodyParam email string required Email address. Example: budi@example.com
     * @bodyParam phone string Phone number. Example: 08123456789
     */
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

    /**
     * Change password
     *
     * Change the authenticated user's password. Requires current password verification.
     *
     * @bodyParam current_password string required Current password. Example: password123
     * @bodyParam new_password string required New password (min 8 chars). Example: newpassword123
     * @bodyParam new_password_confirmation string required Confirm new password. Example: newpassword123
     */
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
