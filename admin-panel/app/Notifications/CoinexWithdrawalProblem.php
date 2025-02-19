<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoinexWithdrawalProblem extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $message, private string $currency, private string $amount)
    {
        //
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

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'در عملیات تجمیع '.$this->currency.'به مقدار '.$this->amount.' روی صرافی کوینکس دچار خطا شدیم. '.' | message: '.$this->message,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('در عملیات تجمیع '.$this->currency.'به مقدار '.$this->amount)
            ->line('در عملیات تجمیع '.$this->currency.'به مقدار '.$this->amount.' روی صرافی کوینکس دچار خطا شدیم. '.' | message: '.$this->message)
            ->greeting('سلام مدیر عزیز');
    }
}
