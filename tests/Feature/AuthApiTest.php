<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_successfully(): void
    {
        $payload = [
            'name' => 'Budi Sanjaya',
            'email' => 'budi@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'role' => 'staff',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'created_at'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'staff',
        ]);
    }

    public function test_register_validation_fails_on_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $payload = [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'loginuser@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => bcrypt('CorrectPassword123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrongpass@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
