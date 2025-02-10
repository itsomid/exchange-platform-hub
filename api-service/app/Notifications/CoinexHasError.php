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
    public function __construct(private string $marketName, private string $amount, private string $errorMessage)
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

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'صرافی ما برای انجام معامله '.$this->marketName.' به مقدار '.formatNumberTrimZeros($this->amount).' دچار خطا شد. خطا: '.$this->errorMessage,
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('هنگام معامله با کوینکس با خطا مواجه شدیم')
            ->line('صرافی ما برای انجام معامله '.$this->marketName.' به مقدار '.formatNumberTrimZeros($this->amount).' دچار خطا شد. خطا: '.$this->errorMessage)
            ->greeting('سلام مدیر عزیز');
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
