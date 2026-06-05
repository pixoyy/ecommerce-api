<?php

namespace Tests\Feature\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password berhasil diubah');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_cannot_change_password_with_wrong_current(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Password saat ini tidak cocok');
    }

    public function test_new_password_minimum_8_characters(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ]);

        $response->assertStatus(422);
    }

    public function test_new_password_confirmation_required(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
            ]);

        $response->assertStatus(422);
    }
}
