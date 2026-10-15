<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\VerifyLinkData;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class VerifyLinkAction
{
    use AsAction;

    /**
     * Redeem a one-time link and issue a token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function handle(VerifyLinkData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $link = LoginLink::where('token_hash', hash('sha256', $data->token))
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($link === null) {
                throw ValidationException::withMessages([
                    'token' => ['El link no es válido o ha expirado. Solicita uno nuevo.'],
                ]);
            }

            $user = $link->user;
            $link->delete();

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()]);
            }

            return [
                'user' => $user,
                'token' => IssueAuthTokenAction::run($user),
            ];
        });
    }
}
