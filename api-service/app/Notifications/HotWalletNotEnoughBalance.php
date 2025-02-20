<?php

namespace App\Notifications;

use App\Helpers\Math;
use App\Models\Market;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HotWalletNotEnoughBalance extends Notification implements ShouldQueue
{
    use Queueable;

    private string $usdtValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $currencyName, private string $amount)
    {

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
            'message' => 'صرافی ما برای برداشت '.$this->currencyName.' به مقدار '.formatNumberTrimZeros($this->amount).' دچار خطا شد. ',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('به علت عدم موجودی هات ولت به مشکل خورده‌ایم')
            ->line($this->getMessage())
            ->greeting('سلام مدیر عزیز');
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
