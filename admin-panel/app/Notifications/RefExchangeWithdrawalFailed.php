<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RefExchangeWithdrawalFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $exchangeName,
        private string $currency,
        private string $amount,
        private string $errorMessage,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => sprintf(
                'در عملیات تجمیع %s به مقدار %s از صرافی مرجع %s دچار خطا شدیم. | message: %s',
                $this->currency,
                $this->amount,
                $this->exchangeName,
                $this->errorMessage
            ),
            'exchange' => $this->exchangeName,
        ];
    }
}
