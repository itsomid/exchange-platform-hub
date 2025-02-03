<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositSuccessful extends Notification
{
    use Queueable;

    protected string $amount;

    protected string $currencySymbol;

    protected string $name;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $currencySymbol, string $amount, string $name)
    {
        $this->currencySymbol = $currencySymbol;
        $this->amount = $amount;
        $this->name = $name;
    }

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
            'message' => 'واریز '.$this->currencySymbol.' به مقدار '.$this->amount.' با موفقیت انجام شد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('واریز موفق')
            ->line('واریز '.$this->currencySymbol.' به مقدار '.$this->amount.' با موفقیت انجام شد.')
            ->action('مشاهده تراکنش', url('/transactions'))
            ->line('متشکریم که از پلتفرم ما استفاده می کنید!')
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
