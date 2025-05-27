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
        $this->onQueue('api-email');
        if ($this->marketName === 'USDT') {
            $this->usdtValue = $this->amount;

            return;
        }
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

    private function getMessage(): string
    {
        if ($this->marketName === 'USDT') {
            $message = 'صرافی ما برای انجام معامله '.$this->amount.' '.$this->marketName.' در کوینکس نیاز دارد.';
        } else {
            $message = 'صرافی ما برای انجام معامله OTC به مقدار '.$this->amount.' '.$this->marketName.' به مقدار تقریبی '.formatNumberTrimZeros($this->usdtValue).' تتر در کوینکس نیاز دارد.';
        }

        return $message;
    }

    public function toDatabase($notifiable): array
    {

        return [
            'message' => $this->getMessage(),
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
            ->view('mail.admin.coinex.not-enough-balance', [
                'messageText' => $this->getMessage(),
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
