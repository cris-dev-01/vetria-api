<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class LoginAction
{
    use AsAction;

    private static ?string $dummyHash = null;

    /**
     * Validate the credentials and issue a token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function handle(LoginData $data): array
    {
        $user = User::where('email', $data->email)->first();

        // Always verify a hash so the response time does not reveal whether the email exists.
        $hash = $user?->password ?? (self::$dummyHash ??= Hash::make('dummy'));
        $valid = Hash::check($data->password, $hash);

        if ($user === null || ! $valid) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son válidas.'],
            ]);
        }

        return [
            'user' => $user,
            'token' => IssueAuthTokenAction::run($user),
        ];
    }
}
