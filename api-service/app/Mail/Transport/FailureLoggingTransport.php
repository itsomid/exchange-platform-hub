<?php

namespace App\Mail\Transport;

use App\Services\Mail\MailFailureLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class FailureLoggingTransport implements TransportInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly MailFailureLogger $logger,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        try {
            return $this->transport->send($message, $envelope);
        } catch (TransportExceptionInterface $exception) {
            try {
                $this->logger->transportFailure($message, $envelope, $exception);
            } catch (\Throwable) {
                // Never let diagnostics hide the original transport failure.
            }

            throw $exception;
        }
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    public function __toString(): string
    {
        return (string) $this->transport;
    }
}
