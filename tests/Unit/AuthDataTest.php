<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Auth\IssueAuthTokenAction;
use App\Data\Auth\LoginData;
use App\Data\Auth\RequestLinkData;
use App\Data\Auth\VerifyLinkData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dtos_are_hydrated_from_validated_data(): void
    {
        $login = LoginData::from(['email' => 'ana@example.com', 'password' => 'secret']);
        $link = RequestLinkData::from(['email' => 'ana@example.com']);
        $verify = VerifyLinkData::from(['token' => 'abc']);

        $this->assertSame('ana@example.com', $login->email);
        $this->assertSame('secret', $login->password);
        $this->assertSame('ana@example.com', $link->email);
        $this->assertSame('abc', $verify->token);
    }

    public function test_issue_auth_token_action_returns_a_usable_sanctum_token(): void
    {
        $user = User::factory()->create();

        $token = IssueAuthTokenAction::run($user);

        $this->assertNotNull($user->fresh()->last_logged_in_at);
        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
    }
}
