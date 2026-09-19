<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletChainNotFound extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $userId,
        private string $currencySymbol,
        private string $chainSymbol,
        private string $availableChains,
        private int $walletChainId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'blockchain_name برای زنجیره پیدا نشد هنگام تولید آدرس کیف پول.'
                . ' | کاربر: ' . $this->userId
                . ' | ارز: ' . $this->currencySymbol
                . ' | زنجیره درخواستی: ' . $this->chainSymbol
                . ' | زنجیره‌های موجود: ' . $this->availableChains
                . ' | WalletChain ID: ' . $this->walletChainId,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
