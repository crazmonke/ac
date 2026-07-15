<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[공동주택 커뮤니티] 비밀번호 변경 안내');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset');
    }
}
