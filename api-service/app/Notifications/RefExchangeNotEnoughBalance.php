<?php

namespace App\Notifications;

use App\Helpers\Math;
use App\Models\Market;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefExchangeNotEnoughBalance extends Notification implements ShouldQueue
{
    use Queueable;

    private string $usdtValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $exchangeName,
        private string $marketName,
        private string $amount,
        private string $orderType = 'sell'
    ) {
        $this->onQueue('api-email');
        if ($this->marketName === 'USDT') {
            $this->usdtValue = $this->amount;

            return;
        }
        $this->marketName = str_replace('USDT', '', $this->marketName);
        $market = Market::query()
            ->where('base_currency', $this->marketName)
            ->first();

        $this->usdtValue = $market ? Math::mul($market->exchangePrice->price, $amount) : $amount;
    }

    /**
     * Get the unique identifier for the notification.
     */
    public function uniqueId(): string
    {
        return $this->exchangeName . '-' . $this->marketName . '-' . $this->amount . '-' . $this->orderType . '-' . time();
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
        $orderTypeText = $this->orderType === 'sell' ? 'فروش' : 'خرید';

        if ($this->marketName === 'USDT') {
            $message = sprintf(
                'صرافی مرجع %s موجودی کافی برای انجام معامله %s به مقدار %s %s ندارد. معامله با موجودی داخلی تکمیل شد.',
                $this->exchangeName,
                $orderTypeText,
                formatNumberTrimZeros($this->amount),
                $this->marketName
            );
        } else {
            $message = sprintf(
                'صرافی مرجع %s موجودی کافی برای انجام معامله %s %s %s (به ارزش تقریبی %s تتر) .ندارد. نسبت به تامین موجودی و فروش آن در صرافی مرجع اقدام کنید',
                $this->exchangeName,
                $orderTypeText,
                formatNumberTrimZeros($this->amount),
                $this->marketName,
                formatNumberTrimZeros($this->usdtValue)
            );
        }

        return $message;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'url' => '/transactions',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('عدم موجودی صرافی مرجع ' . $this->exchangeName)
            ->view('mail.admin.ref-exchange.not-enough-balance', [
                'messageText' => $this->getMessage(),
                'exchangeName' => $this->exchangeName,
                'marketName' => $this->marketName,
                'amount' => $this->amount,
                'orderType' => $this->orderType,
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
