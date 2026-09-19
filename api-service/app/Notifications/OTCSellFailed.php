<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTCSellFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int    $otcOrderId,
        private int    $userId,
        private string $marketName,
        private string $sellAmount,
        private string $receivedAmount,
        private string $buyerQuoteWalletBalance,
        private string $reason,
    ) {
        $this->onQueue('api-email');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function getMessage(): string
    {
        return sprintf(
            "سفارش فروش #%d با شکست روبرو شد.\n\nجزئیات:\n- کاربر: %d\n- بازار: %s\n- مقدار فروش: %s\n- مبلغ قابل دریافت: %s تتر\n- موجودی ولت تتر خریدار (bitexroom): %s تتر\n- دلیل: %s",
            $this->otcOrderId,
            $this->userId,
            $this->marketName,
            formatNumberTrimZeros((float) $this->sellAmount),
            formatNumberTrimZeros((float) $this->receivedAmount),
            formatNumberTrimZeros((float) $this->buyerQuoteWalletBalance),
            $this->reason,
        );
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'url' => '/otc-orders/' . $this->otcOrderId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('شکست سفارش فروش OTC #' . $this->otcOrderId)
            ->greeting('هشدار: شکست سفارش فروش')
            ->line('سفارش فروش زیر با شکست روبرو شد و به صورت خودکار لغو شد.')
            ->line('**شناسه سفارش:** ' . $this->otcOrderId)
            ->line('**کاربر:** ' . $this->userId)
            ->line('**بازار:** ' . $this->marketName)
            ->line('**مقدار فروش:** ' . formatNumberTrimZeros((float) $this->sellAmount))
            ->line('**مبلغ قابل دریافت:** ' . formatNumberTrimZeros((float) $this->receivedAmount) . ' تتر')
            ->line('**موجودی ولت تتر خریدار (bitexroom):** ' . formatNumberTrimZeros((float) $this->buyerQuoteWalletBalance) . ' تتر')
            ->line('**دلیل شکست:** ' . $this->reason)
            ->action('مشاهده سفارش', url('/admin/otc-orders/' . $this->otcOrderId));
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
