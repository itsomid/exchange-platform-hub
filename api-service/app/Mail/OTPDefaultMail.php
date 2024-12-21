<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTPDefaultMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly int $code, private readonly ?string $name) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'کد دو عاملی',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $expiration = now()->addMinutes(15)->diffForHumans();

        return new Content(
            view: 'mail.otp.default-mail',
            with: ['code' => $this->code, 'name' => $this->name, 'expiration' => $expiration],
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
