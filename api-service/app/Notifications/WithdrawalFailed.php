<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $currencyName,
        private string $amount,
        private User $user,
        private string $description
    ) {
        $this->onQueue('api-email');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'برداشت '.$this->currencyName.' به مقدار '.formatNumberTrimZeros($this->amount).' برای کاربر '.$this->user->username.' (ID: '.$this->user->id.') با خطا مواجه شد. علت: '.$this->description,
            'url' => '/transactions',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('خطا در برداشت '.$this->currencyName)
            ->greeting('سلام مدیر عزیز')
            ->line('برداشت '.$this->currencyName.' به مقدار '.formatNumberTrimZeros($this->amount).' برای کاربر '.$this->user->username.' (ID: '.$this->user->id.') با خطا مواجه شد.')
            ->line('علت خطا: '.$this->description);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
