<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];
    }

    public function test_user_can_register()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/auth/register', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Please verify your email address.',
                'requires_verification' => true
            ]);

        $this->assertDatabaseHas('registration_attempts', ['email' => 'test@example.com']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders($this->headers)->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_with_invalid_credentials_fails()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->withHeaders($this->headers)->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $response = $this->withHeaders($this->headers)->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $response = $this->withHeaders($this->headers)->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_request_returns_401()
    {
        $response = $this->withHeaders($this->headers)->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_missing_api_key_returns_401()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_invalid_api_key_returns_403()
    {
        $response = $this->withHeaders(['X-API-Key' => 'invalid_key'])->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create();
        $userToUpdate = User::factory()->create(['name' => 'Old Name']);
        $role = \App\Models\Role::firstOrCreate(['name' => 'user'], ['label' => 'User']);

        $token = $admin->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $response = $this->withHeaders($this->headers)->putJson("/api/users/{$userToUpdate->id}", [
            'name' => 'New Name',
            'email' => $userToUpdate->email,
            'role_id' => $role->id,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
        ]);
    }
}
