<?php

namespace Tests\Feature\Api\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'id', 'name', 'email', 'phone', 'point_balance', 'created_at',
                ],
            ]);
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '08123456789',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.phone', '08123456789');
    }

    public function test_email_unique_validation_ignores_current_user(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', [
                'name' => 'Updated',
                'email' => 'same@example.com',
            ]);

        $response->assertOk();
    }

    public function test_email_unique_validation_rejects_duplicate(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', [
                'name' => 'Updated',
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422);
    }
}
