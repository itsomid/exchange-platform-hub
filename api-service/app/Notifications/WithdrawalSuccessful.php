<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Mail\WithdrawalMail;

class WithdrawalSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $amount;

    protected string $currencySymbol;

    protected string $name;

    protected string $network;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $currencySymbol, string $amount, string $name, string $network)
    {
        $this->onQueue('api-email');
        $this->currencySymbol = $currencySymbol;
        $this->amount = $amount;
        $this->name = $name;
        $this->network = $network;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail']; // Send via database and email
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'برداشت '.$this->currencySymbol.' به مقدار '.formatNumberTrimZeros($this->amount).' با موفقیت انجام شد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): WithdrawalMail
    {

        return new WithdrawalMail(
            $this->currencySymbol,
            $this->amount,
            $this->network
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
