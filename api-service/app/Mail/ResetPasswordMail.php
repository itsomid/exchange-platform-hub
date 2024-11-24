<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly User $user, private string $url)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('mehdints@gmail.com', 'Mehdi Rajabi'),
            to: [$this->user->email],
            subject: 'بازبابی رمز عبور',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {

        $expiration = now()->addMinutes(config('auth.passwords.users.expire'))->diffForHumans();

        return new Content(
            view: 'mail.auth.reset-password',
            with: [
                'name' => $this->user->name,
                'url' => $this->url,
                'expiration' => $expiration,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
