<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RequestLinkData;
use App\Mail\LoginLinkMail;
use App\Models\LoginLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class RequestLinkAction
{
    use AsAction;

    public const EXPIRES_IN_MINUTES = 15;

    /**
     * Issue a one-time login link for the user, if the email belongs to one.
     */
    public function handle(RequestLinkData $data): void
    {
        LoginLink::where('expires_at', '<', now())->delete();

        $user = User::where('email', $data->email)->first();

        if ($user === null) {
            return;
        }

        $token = Str::random(64);

        DB::transaction(function () use ($user, $token): void {
            LoginLink::where('user_id', $user->id)->delete();

            LoginLink::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
            ]);
        });

        Mail::to($user)->queue(new LoginLinkMail($user, $token, self::EXPIRES_IN_MINUTES));
    }
}
