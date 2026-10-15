<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Mail\LoginLinkMail;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LinkAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_MESSAGE = 'Si el correo está registrado, recibirás un link de acceso.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_url' => 'http://app.vetria.test']);
        Mail::fake();
    }

    private function user(string $email = 'ana@example.com'): User
    {
        return User::factory()->create(['email' => $email]);
    }

    /**
     * Request a link and return the plain token that was mailed.
     */
    private function requestToken(User $user): string
    {
        $this->postJson('/api/auth/link', ['email' => $user->email])->assertStatus(202);

        $token = null;
        Mail::assertQueued(LoginLinkMail::class, function (LoginLinkMail $mail) use ($user, &$token): bool {
            if ($mail->hasTo($user->email)) {
                $token = $mail->token;

                return true;
            }

            return false;
        });

        return $token;
    }

    public function test_requesting_a_link_for_an_existing_user_sends_an_email(): void
    {
        $user = $this->user();

        $this->postJson('/api/auth/link', ['email' => $user->email])
            ->assertStatus(202)
            ->assertExactJson(['message' => self::GENERIC_MESSAGE]);

        Mail::assertQueued(LoginLinkMail::class, 1);
        $this->assertSame(1, LoginLink::where('user_id', $user->id)->count());
    }

    public function test_requesting_a_link_for_an_unknown_email_returns_the_same_response_and_sends_nothing(): void
    {
        $this->postJson('/api/auth/link', ['email' => 'nadie@example.com'])
            ->assertStatus(202)
            ->assertExactJson(['message' => self::GENERIC_MESSAGE]);

        Mail::assertNothingQueued();
        $this->assertSame(0, LoginLink::count());
    }

    public function test_requesting_a_link_validates_the_email(): void
    {
        $this->postJson('/api/auth/link', [])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/api/auth/link', ['email' => 'no-es-un-correo'])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_the_link_points_to_the_frontend_and_contains_the_token(): void
    {
        $user = $this->user();
        $token = $this->requestToken($user);

        Mail::assertQueued(LoginLinkMail::class, function (LoginLinkMail $mail) use ($token): bool {
            $this->assertSame('http://app.vetria.test/auth/link?token='.$token, $mail->url());
            $this->assertStringContainsString('http://app.vetria.test/auth/link?token='.$token, $mail->render());
            $this->assertStringContainsString('15 minutos', $mail->render());

            return true;
        });
    }

    public function test_only_the_token_hash_is_stored_and_it_expires_in_fifteen_minutes(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->user();
        $token = $this->requestToken($user);

        $link = LoginLink::firstOrFail();

        $this->assertSame(64, strlen($token));
        $this->assertSame(hash('sha256', $token), $link->token_hash);
        $this->assertNotSame($token, $link->token_hash);
        $this->assertEquals(now()->addMinutes(15), $link->expires_at);
    }

    public function test_a_new_request_replaces_the_previous_link(): void
    {
        $user = $this->user();
        $first = $this->requestToken($user);
        Mail::fake();
        $second = $this->requestToken($user);

        $this->assertSame(1, LoginLink::where('user_id', $user->id)->count());

        $this->postJson('/api/auth/link/verify', ['token' => $first])->assertStatus(422);
        $this->postJson('/api/auth/link/verify', ['token' => $second])->assertOk();
    }

    public function test_a_new_request_removes_expired_links_of_other_users(): void
    {
        $stale = $this->user('vieja@example.com');
        LoginLink::create([
            'user_id' => $stale->id,
            'token_hash' => hash('sha256', 'stale'),
            'expires_at' => now()->subMinute(),
        ]);
        $pending = $this->user('pendiente@example.com');
        LoginLink::create([
            'user_id' => $pending->id,
            'token_hash' => hash('sha256', 'pending'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->requestToken($this->user());

        $this->assertSame(0, LoginLink::where('user_id', $stale->id)->count());
        $this->assertSame(1, LoginLink::where('user_id', $pending->id)->count());
    }

    public function test_redeeming_a_valid_token_returns_an_access_token_and_deletes_the_link(): void
    {
        $user = $this->user();
        $token = $this->requestToken($user);

        $response = $this->postJson('/api/auth/link/verify', ['token' => $token]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'last_name', 'email']]])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password');
        $this->assertSame(0, LoginLink::count());

        $this->withToken($response->json('data.token'))->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
    }

    public function test_a_token_can_only_be_redeemed_once(): void
    {
        $token = $this->requestToken($this->user());

        $this->postJson('/api/auth/link/verify', ['token' => $token])->assertOk();
        $this->postJson('/api/auth/link/verify', ['token' => $token])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_an_expired_token_is_rejected_without_changing_the_user(): void
    {
        $user = $this->user();
        $user->forceFill(['email_verified_at' => null])->save();
        $token = $this->requestToken($user);

        $this->travel(16)->minutes();

        $this->postJson('/api/auth/link/verify', ['token' => $token])->assertStatus(422);

        $user->refresh();
        $this->assertNull($user->last_logged_in_at);
        $this->assertNull($user->email_verified_at);
    }

    public function test_every_invalid_token_gets_the_same_error(): void
    {
        $user = $this->user();
        $used = $this->requestToken($user);
        $this->postJson('/api/auth/link/verify', ['token' => $used])->assertOk();

        $expired = $this->requestToken($user);
        $this->travel(16)->minutes();

        $responses = [
            $this->postJson('/api/auth/link/verify', ['token' => $used])->json(),
            $this->postJson('/api/auth/link/verify', ['token' => $expired])->json(),
            $this->postJson('/api/auth/link/verify', ['token' => 'inexistente'])->json(),
        ];

        $this->assertCount(1, array_unique(array_map('json_encode', $responses)));
    }

    public function test_redeeming_without_a_token_fails_validation(): void
    {
        $this->postJson('/api/auth/link/verify', [])->assertStatus(422)->assertJsonValidationErrors('token');
    }

    public function test_redeeming_updates_last_logged_in_at_and_verifies_the_email(): void
    {
        $user = $this->user();
        $user->forceFill(['email_verified_at' => null])->save();
        $token = $this->requestToken($user);

        $this->travelTo(now()->startOfSecond());
        $this->postJson('/api/auth/link/verify', ['token' => $token])->assertOk();

        $user->refresh();
        $this->assertEquals(now(), $user->last_logged_in_at);
        $this->assertEquals(now(), $user->email_verified_at);
    }

    public function test_redeeming_keeps_an_existing_email_verification_date(): void
    {
        $verifiedAt = now()->subYear()->startOfSecond();
        $user = $this->user();
        $user->forceFill(['email_verified_at' => $verifiedAt])->save();
        $token = $this->requestToken($user);

        $this->postJson('/api/auth/link/verify', ['token' => $token])->assertOk();

        $this->assertEquals($verifiedAt, $user->fresh()->email_verified_at);
    }

    public function test_get_requests_never_consume_the_link(): void
    {
        $token = $this->requestToken($this->user());

        $this->getJson('/api/auth/link/verify?token='.$token)->assertStatus(405);

        $this->assertSame(1, LoginLink::count());
        $this->postJson('/api/auth/link/verify', ['token' => $token])->assertOk();
    }

    public function test_link_requests_are_rate_limited_per_email_and_ip(): void
    {
        $user = $this->user();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/link', ['email' => $user->email])->assertStatus(202);
        }

        $this->postJson('/api/auth/link', ['email' => $user->email])->assertStatus(429);
        Mail::assertQueued(LoginLinkMail::class, 3);
    }

    public function test_link_redemptions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/link/verify', ['token' => 'inexistente'])->assertStatus(422);
        }

        $this->postJson('/api/auth/link/verify', ['token' => 'inexistente'])->assertStatus(429);
    }
}
