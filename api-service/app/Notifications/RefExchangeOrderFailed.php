<?php

namespace App\Notifications;

use App\Enums\SpotStatusEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An order on the reference exchange failed. Each exchange adapter maps its own
 * error codes onto SpotStatusEnum, so this notification never deals with them.
 */
class RefExchangeOrderFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $exchangeName,
        private SpotStatusEnum $reason,
        private string $marketName,
        private string $amount,
        private string $side,
        private ?string $errorMessage = null,
        private ?int $errorCode = null,
        private ?string $tradeType = null,
        private ?int $userId = null,
        private ?int $orderId = null,
    ) {
        $this->onQueue('api-email');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'url' => $this->getUrl(),
            'exchange' => $this->exchangeName,
            'reason' => $this->reason->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle() . ' (' . $this->exchangeName . ')')
            ->view('mail.admin.ref-exchange.order-failed', [
                'title' => $this->getTitle(),
                'messageText' => $this->getMessage(),
                'details' => $this->getDetails(),
            ]);
    }

    private function getTitle(): string
    {
        return match ($this->reason) {
            SpotStatusEnum::AmountTooSmall => 'مقدار سفارش کمتر از حداقل مجاز صرافی مرجع است',
            SpotStatusEnum::PriceDifferenceTooLarge => 'قیمت سفارش با آخرین قیمت بازار صرافی مرجع اختلاف زیادی دارد',
            default => 'خطا در ثبت سفارش در صرافی مرجع',
        };
    }

    private function getMessage(): string
    {
        $parts = [
            sprintf(
                '%s: %s %s بازار %s به مقدار %s در %s.',
                $this->getTitle(),
                $this->getSideLabel(),
                $this->getTradeTypeLabel(),
                $this->marketName,
                formatNumberTrimZeros($this->amount),
                $this->exchangeName
            ),
        ];

        if ($this->orderId !== null) {
            $parts[] = sprintf('%s: #%d', $this->getOrderLabel(), $this->orderId);
        }

        if ($this->userId !== null) {
            $parts[] = sprintf('کاربر: #%d', $this->userId);
        }

        if ($this->errorMessage !== null && $this->errorMessage !== '') {
            $parts[] = 'خطا: ' . $this->errorMessage . ($this->errorCode ? ' (کد ' . $this->errorCode . ')' : '');
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, string>
     */
    private function getDetails(): array
    {
        $details = [
            'صرافی مرجع' => $this->exchangeName,
            'نوع معامله' => $this->getTradeTypeLabel(),
            'سمت' => $this->getSideLabel(),
            'بازار' => $this->marketName,
            'مقدار' => formatNumberTrimZeros($this->amount),
        ];

        if ($this->orderId !== null) {
            $details[$this->getOrderLabel()] = '#' . $this->orderId;
        }

        if ($this->userId !== null) {
            $details['کاربر'] = '#' . $this->userId;
        }

        if ($this->errorCode) {
            $details['کد خطا'] = (string) $this->errorCode;
        }

        if ($this->errorMessage !== null && $this->errorMessage !== '') {
            $details['پیام صرافی'] = $this->errorMessage;
        }

        return $details;
    }

    private function getSideLabel(): string
    {
        return strtolower($this->side) === 'sell' ? 'فروش' : 'خرید';
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

    private function getUrl(): string
    {
        return match ($this->tradeType) {
            'otc' => '/otc_orders',
            'spot' => '/spot/trades',
            default => '/transactions',
        };
    }
}
