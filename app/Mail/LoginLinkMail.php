<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public int $expiresInMinutes,
    ) {}

    public function url(): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/auth/link?'.http_build_query(['token' => $this->token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu link de acceso a '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.link',
            with: ['url' => $this->url()],
        );
    }
}
