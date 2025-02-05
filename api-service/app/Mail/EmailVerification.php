<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public string $url)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('info@bitexroom.com', 'BitexRoom'),
            to: [$this->user->email],
            subject: 'فعال‌سازی حساب کاربری',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $emailVerificationModel = $this->user->emailVerification()->latest()->first();
        $token = $this->user->getLatestToken();
        $expirationDate = $emailVerificationModel->expiration_date->diffForHumans();

        return new Content(
            view: 'mail.auth.email-verification',
            with: [
                'user' => $this->user,
                'token' => $token,
                'url' => $this->url,
                'expirationDate' => $expirationDate,
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
