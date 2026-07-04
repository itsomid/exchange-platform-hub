<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinexHasError extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $marketName,
        private string $amount,
        private string $errorMessage,
        private ?string $tradeType = null,
        private ?int $userId = null,
        private ?int $orderId = null,
    ) {
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

    private function getTradeTypeLabel(): string
    {
        return match ($this->tradeType) {
            'otc' => 'OTC',
            'spot' => 'اسپات',
            default => 'نامشخص',
        };
    }

    private function getOrderLabel(): string
    {
        return match ($this->tradeType) {
            'otc' => 'سفارش OTC',
            'spot' => 'معامله اسپات',
            default => 'سفارش',
        };
    }

    private function getMessage(): string
    {
        $parts = [
            sprintf(
                'خطا در معامله %s بازار %s به مقدار %s.',
                $this->getTradeTypeLabel(),
                $this->marketName,
                formatNumberTrimZeros($this->amount)
            ),
        ];

        if ($this->orderId !== null) {
            $parts[] = sprintf('%s: #%d', $this->getOrderLabel(), $this->orderId);
        }

        if ($this->userId !== null) {
            $parts[] = sprintf('کاربر: #%d', $this->userId);
        }

        $parts[] = 'خطا: '.$this->errorMessage;

        return implode(' ', $parts);
    }

    private function getUrl(): string
    {
        return match ($this->tradeType) {
            'otc' => '/otc_orders',
            'spot' => '/spot/trades',
            default => '/transactions',
        };
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'url' => $this->getUrl(),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('خطا در معامله کوینکس ('.$this->getTradeTypeLabel().')')
            ->greeting('سلام مدیر عزیز')
            ->line('هنگام معامله با کوینکس با خطا مواجه شدیم.')
            ->line('**نوع معامله:** '.$this->getTradeTypeLabel())
            ->line('**بازار:** '.$this->marketName)
            ->line('**مقدار:** '.formatNumberTrimZeros($this->amount));

        if ($this->orderId !== null) {
            $mail->line('**'.$this->getOrderLabel().':** #'.$this->orderId);
        }

        if ($this->userId !== null) {
            $mail->line('**کاربر:** #'.$this->userId);
        }

        return $mail->line('**خطا:** '.$this->errorMessage);
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
