<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_validates_input(): void
    {
        $this->postJson('/api/auth/register', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_a_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_login_fails_with_bad_credentials(): void
    {
        User::factory()->create(['email' => 'john@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_api_returns_json_401_without_accept_header(): void
    {
        // A protected route with no token and no Accept header must still return a
        // JSON 401 — never a 500 from an attempted redirect to a web login route.
        $this->get('/api/orders', ['Accept' => 'text/html'])
            ->assertStatus(401)
            ->assertHeader('content-type', 'application/json')
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_logout_invalidates_the_token(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->json('access_token');

        $auth = ['Authorization' => "Bearer {$token}"];

        // The token works before logout...
        $this->getJson('/api/auth/me', $auth)->assertOk();

        $this->postJson('/api/auth/logout', [], $auth)->assertOk();

        // ...and is rejected after.
        $this->getJson('/api/auth/me', $auth)->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        $payload = ['email' => 'nobody@example.com', 'password' => 'wrong'];

        // The limiter allows 6 attempts per minute; the 7th is throttled (429).
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', $payload)->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', $payload)->assertStatus(429);
    }
}
