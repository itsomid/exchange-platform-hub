<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HotWalletNotEnoughBalance extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $currency,
        private string $amount,
        private string $chain = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $chainInfo = $this->chain ? ' (شبکه: ' . $this->chain . ')' : '';

        return [
            'message' => 'Hot Wallet موجودی کافی برای پردازش ندارد. ارز: ' . $this->currency . $chainInfo . ' | مقدار مورد نیاز: ' . formatNumberTrimZeros($this->amount),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $chainInfo = $this->chain ? ' (شبکه: ' . $this->chain . ')' : '';

        return (new MailMessage)
            ->subject('عدم موجودی Hot Wallet - ' . $this->currency)
            ->greeting('سلام مدیر عزیز')
            ->line('Hot Wallet موجودی کافی برای پردازش ندارد.')
            ->line('ارز: ' . $this->currency . $chainInfo)
            ->line('مقدار مورد نیاز: ' . formatNumberTrimZeros($this->amount));
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
