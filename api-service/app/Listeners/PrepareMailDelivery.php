<?php

namespace App\Listeners;

use App\Mail\Transport\FailureLoggingTransport;
use App\Services\Mail\MailFailureLogger;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class PrepareMailDelivery
{
    public function __construct(private readonly MailFailureLogger $logger) {}

    /**
     * Reject obviously invalid recipients and start each message on a fresh
     * SMTP connection.
     *
     * Mail providers such as Aliyun reject an unusable recipient with a 559
     * "Invalid rcptto" response and then drop the socket. Queue workers are
     * long-running processes that keep the SMTP socket between jobs, so the
     * next message tries to write to that dead socket and fails with
     * "Unable to write bytes on the wire". Closing the connection before
     * every send keeps one bad recipient from breaking the following mails.
     */
    public function handle(MessageSending $event): ?bool
    {
        $invalidRecipients = $this->invalidRecipients($event->message);

        if ($invalidRecipients !== []) {
            $this->logger->invalidRecipients($event->message, $invalidRecipients);

            return false;
        }

        $transport = Mail::mailer($event->data['mailer'] ?? null)->getSymfonyTransport();

        if ($transport instanceof FailureLoggingTransport) {
            $transport = $transport->getTransport();
        }

        if ($transport instanceof SmtpTransport) {
            $transport->stop();
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function invalidRecipients(Email $message): array
    {
        return collect([...$message->getTo(), ...$message->getCc(), ...$message->getBcc()])
            ->map(fn (Address $address) => $address->getAddress())
            ->reject(fn (string $address) => filter_var($address, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}
