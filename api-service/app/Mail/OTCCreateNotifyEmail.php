<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTCCreateNotifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private readonly string $currencySymbol, private readonly string $quantity, private readonly string $type)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = [
            'buy' => 'سفارش خرید شما با موفقیت انجام شد.',
            'sell' => 'سفارش فروش شما با موفقیت انجام شد.',
        ];

        return new Envelope(
            subject: $subject[$this->type],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.otc.otc-order-completed',
            with: [
                'currencySymbol' => $this->currencySymbol,
                'quantity' => $this->quantity,
                'type' => $this->type,
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
