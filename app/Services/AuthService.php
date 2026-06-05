<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            UserPoint::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            return $user;
        });
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'email' => ['Akun telah dinonaktifkan.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function me(User $user): User
    {
        return $user->load('userPoints');
    }

    public function updateProfile(int $userId, array $data): User
    {
        $user = User::findOrFail($userId);
        $user->update(Arr::only($data, ['name', 'phone', 'email']));
        return $user->fresh();
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $user = User::findOrFail($userId);

        if (!Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Password saat ini tidak cocok');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }
}
