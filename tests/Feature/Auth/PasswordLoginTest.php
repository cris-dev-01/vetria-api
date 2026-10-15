<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordLoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'secret-password',
        ]);
    }

    public function test_valid_credentials_return_a_token_and_user(): void
    {
        $user = $this->user();

        $response = $this->postJson('/api/login', [
            'email' => 'ana@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'last_name', 'email']]])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');

        $this->withToken($response->json('data.token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_wrong_password_is_rejected_without_token(): void
    {
        $this->user();

        $this->postJson('/api/login', [
            'email' => 'ana@example.com',
            'password' => 'wrong',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonMissingPath('data.token');
    }

    public function test_unknown_email_returns_the_same_error_as_wrong_password(): void
    {
        $this->user();

        $wrongPassword = $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'wrong']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'nadie@example.com', 'password' => 'wrong']);

        $unknownEmail->assertStatus(422)->assertJsonMissingPath('data.token');
        $this->assertSame($wrongPassword->json(), $unknownEmail->json());
    }

    public function test_missing_or_invalid_fields_fail_validation(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/login', ['email' => 'no-es-un-correo', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonMissingValidationErrors('password');
    }

    public function test_successful_login_updates_last_logged_in_at(): void
    {
        $user = $this->user();
        $this->assertNull($user->last_logged_in_at);

        $this->travelTo(now()->startOfSecond());
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'secret-password'])->assertOk();

        $this->assertEquals(now(), $user->fresh()->last_logged_in_at);
    }

    public function test_failed_login_does_not_update_last_logged_in_at(): void
    {
        $user = $this->user();

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'wrong'])->assertStatus(422);

        $this->assertNull($user->fresh()->last_logged_in_at);
    }

    public function test_login_attempts_are_rate_limited_per_email_and_ip(): void
    {
        $this->user();
        $payload = ['email' => 'ana@example.com', 'password' => 'wrong'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $payload)->assertStatus(422);
        }

        $this->postJson('/api/login', $payload)->assertStatus(429);
        // Even correct credentials are not evaluated once the limit is exceeded.
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'secret-password'])
            ->assertStatus(429)
            ->assertJsonMissingPath('data.token');
    }
}
