<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTCSellCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $currencySymbol, private string $amount, private string $name) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail']; // Send via database and email
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'درخواست فروش سریع '.$this->currencySymbol.' به مقدار '.$this->amount.' با موفقیت انجام شد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('درخواست فروش سریع')
            ->line('درخواست فروش سریع '.$this->currencySymbol.' به مقدار '.$this->amount.' با موفقیت انجام شد.')
            ->line('متشکریم که از پلتفرم ما استفاده می کنید!')
            ->action('مشاهده تراکنش', url('/transactions'))
            ->greeting("سلام {$this->name} عزیز");
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
