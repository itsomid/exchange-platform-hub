<?php

namespace App\Notifications;

use App\Helpers\Math;
use App\Models\Market;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinexNotEnoughBalance extends Notification implements ShouldQueue
{
    use Queueable;

    private string $usdtValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $marketName, private string $amount)
    {
        $this->marketName = str_replace('USDT', '', $this->marketName);
        $market = Market::query()
            ->where('base_currency', $this->marketName)
            ->first();

        $this->usdtValue = Math::mul($market->exchangePrice->price, $amount);
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
            'message' => 'صرافی ما برای انجام معامله '.$this->amount.' '.$this->marketName.' به مقدار تقریبی '.formatNumberTrimZeros($this->usdtValue).' تتر در کوینکس نیاز دارد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('به علت عدم موجودی کوینکس به مشکل خورده‌ایم')
            ->line('صرافی ما برای انجام معامله '.$this->amount.' '.$this->marketName.' به مقدار تقریبی '.formatNumberTrimZeros($this->usdtValue).' تتر در کوینکس نیاز دارد.')
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
