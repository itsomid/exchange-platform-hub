<?php

namespace App\Services\Mail;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailFailureLogger
{
    /**
     * Log recipients that were rejected before they reached the SMTP server.
     *
     * @param array<int, string> $recipients
     */
    public function invalidRecipients(Email $message, array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $this->write(
                recipient: $recipient,
                subject: $message->getSubject(),
                reason: 'invalid_format',
                detail: 'Recipient address is not a valid email address.',
            );
        }
    }

    /**
     * Log the exact reason the SMTP server refused or dropped a message.
     */
    public function transportFailure(RawMessage $message, ?Envelope $envelope, TransportExceptionInterface $exception): void
    {
        $recipients = array_map(
            fn (Address $address) => $address->getAddress(),
            ($envelope ?? Envelope::create($message))->getRecipients(),
        );

        foreach ($recipients as $recipient) {
            $this->write(
                recipient: $recipient,
                subject: $message instanceof Email ? $message->getSubject() : null,
                reason: $this->reason($exception),
                detail: $exception->getMessage(),
                smtpCode: $exception->getCode() ?: null,
                exception: $exception::class,
            );
        }
    }

    /**
     * Log an account found by the audit command.
     */
    public function accountCannotReceiveMail(User|Admin $account, string $reason, string $detail): void
    {
        $this->channel()->warning('Account cannot receive email.', [
            'reason' => $reason,
            'detail' => $detail,
            'account' => $this->accountContext($account),
            'logged_at' => now()->toIso8601String(),
        ]);
    }

    private function write(
        string $recipient,
        ?string $subject,
        string $reason,
        string $detail,
        ?int $smtpCode = null,
        ?string $exception = null,
    ): void {
        $this->channel()->warning("Email was not sent to {$recipient}.", [
            'recipient' => $recipient,
            'reason' => $reason,
            'detail' => $detail,
            'smtp_code' => $smtpCode,
            'exception' => $exception,
            'subject' => $subject,
            'account' => $this->findAccount($recipient),
            'logged_at' => now()->toIso8601String(),
        ]);
    }

    private function reason(TransportExceptionInterface $exception): string
    {
        if ($exception->getCode() === 559 || str_contains($exception->getMessage(), 'Invalid rcptto')) {
            return 'invalid_recipient';
        }

        if ($exception instanceof UnexpectedResponseException) {
            return 'smtp_rejected';
        }

        return 'connection_failed';
    }

    private function findAccount(string $email): ?array
    {
        try {
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user) {
                return $this->accountContext($user);
            }

            $admin = Admin::query()->where('email', $email)->first();

            return $admin ? $this->accountContext($admin) : null;
        } catch (\Throwable) {
            // Diagnostics must never break the mail delivery flow.
            return null;
        }
    }

    private function accountContext(User|Admin $account): array
    {
        return [
            'type' => $account instanceof Admin ? 'admin' : 'user',
            'id' => $account->id,
            'username' => $account instanceof User ? $account->username : null,
            'first_name' => $account->first_name,
            'last_name' => $account->last_name,
            'email' => $account->email,
            'status' => $account instanceof User
                ? ($account->status instanceof \BackedEnum ? $account->status->value : $account->status)
                : ($account->is_active ? 'active' : 'inactive'),
            'email_verified_at' => $this->date($account->email_verified_at),
            'registered_at' => $this->date($account->created_at),
            'deleted_at' => $account instanceof User ? $this->date($account->deleted_at) : null,
        ];
    }

    private function date(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : ($value ? (string) $value : null);
    }

    private function channel(): LoggerInterface
    {
        return Log::channel('mail-failures');
    }
}
