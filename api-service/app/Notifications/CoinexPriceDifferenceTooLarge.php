<?php

namespace App\Notifications;

use App\Helpers\Math;
use App\Models\Market;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinexPriceDifferenceTooLarge extends Notification implements ShouldQueue
{
    use Queueable;

    private string $usdtValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $marketName, private string $amount, private string $message)
    {
        $this->onQueue('api-email');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {

        return [
            'message' => 'قیمت سفارش'.$this->marketName.' به مقدار '.formatNumberTrimZeros($this->amount).'با آخرین قیمت بازار اختلاف زیادی دارد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('برای تکمیل معامله به مشکل خورده ایم')
            ->view('mail.admin.coinex.price-difference-too-large', [
                'messageText' => $this->message,
                'marketName' => $this->marketName,
                'amount' => $this->amount
            ]);
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
