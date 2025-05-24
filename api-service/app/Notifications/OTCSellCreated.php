<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTCSellCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $currencySymbol, private string $amount, private string $name) {
        $this->onQueue('api-email');
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
            'message' => 'درخواست فروش سریع '.$this->currencySymbol.' به مقدار '.$this->amount.' با موفقیت انجام شد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('سفارش فروش شما تکمیل شد')
            ->view('mail.otc.sell', [
                'name' => $this->name,
                'currencySymbol' => $this->currencySymbol,
                'amount' => $this->amount,
                'baseUrl' => config('app.url'),
                'transactionUrl' => url('/transactions'),
            ]);
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
