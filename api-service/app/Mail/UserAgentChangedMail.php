<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;

class UserAgentChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    public string $newAgent;

    public function __construct(User $user, string $newAgent)
    {
        $this->user = $user;
        $this->newAgent = $newAgent;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->user->email],
            subject: 'دستگاه شما تغییر کرده است',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $agent = new Agent;
        $agent->setUserAgent($this->newAgent);

        return new Content(
            view: 'mail.auth.change-user-agent',
            with: [
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'device' => $agent->device(),
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
