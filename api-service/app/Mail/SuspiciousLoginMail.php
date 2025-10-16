<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;

class SuspiciousLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->data['user']->email],
            subject: 'هشدار امنیتی: ورود مشکوک به حساب کاربری',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $agent = new Agent();
        
        // استفاده از user_agent موجود در داده‌های ارسالی
        if (isset($this->data['user_agent'])) {
            $agent->setUserAgent($this->data['user_agent']);
        }
        
        // افزودن اطلاعات دستگاه به داده‌های ارسالی
        $this->data['device_info'] = [
            'platform' => $agent->platform(),
            'browser' => $agent->browser(),
            'device' => $agent->device() ?: 'کامپیوتر',
        ];

        return new Content(
            view: 'mail.security.suspicious-login',
            with: $this->data
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
