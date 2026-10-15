<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class IssueAuthTokenAction
{
    use AsAction;

    /**
     * Record the access and issue a Sanctum token for the user.
     */
    public function handle(User $user): string
    {
        $user->forceFill(['last_logged_in_at' => now()])->save();

        return $user->createToken('auth')->plainTextToken;
    }
}
