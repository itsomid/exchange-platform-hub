<?php

namespace App\Mail;

use App\Enums\EmailOTPActionEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTPDefaultMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly int $code, private readonly ?string $name = null, private readonly EmailOTPActionEnum $action)
    {
        $this->onQueue('api-email');
    }

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
        $expiration = now()->addMinutes(10)->diffForHumans();

        return new Content(
            view: 'mail.otp.default-mail',
            with: [
                'code' => $this->code,
                'expiration' => $expiration,
                'action' => $this->action
            ],
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
