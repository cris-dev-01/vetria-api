<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_link_is_created_with_uuid_and_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $link = LoginLink::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'token'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue(Str::isUuid($link->id));
        $this->assertTrue($link->user->is($user));
        $this->assertTrue($user->loginLinks->first()->is($link));
        $this->assertTrue($link->expires_at->isFuture());
    }

    public function test_user_can_only_have_one_login_link(): void
    {
        $user = User::factory()->create();
        $attributes = ['user_id' => $user->id, 'expires_at' => now()->addMinutes(15)];

        LoginLink::create($attributes + ['token_hash' => hash('sha256', 'one')]);

        $this->expectException(QueryException::class);
        LoginLink::create($attributes + ['token_hash' => hash('sha256', 'two')]);
    }
}
