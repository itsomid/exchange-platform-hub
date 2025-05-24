<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HotWalletNotEnoughBalance extends Notification implements ShouldQueue
{
    use Queueable;

    private string $usdtValue;

    /**
     * Create a new notification instance.
     */
    public function __construct(private string $currencyName, private string $amount, private User $user) {
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

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'صرافی ما برای برداشت '.$this->currencyName.' به مقدار '.formatNumberTrimZeros($this->amount).' از هات ولت کاربر '.$this->user->username.' (ID: '.$this->user->id.') به علت عدم موجودی دچار خطا شد.',
            'url' => '/transactions', // Optional: URL to redirect to
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('به علت عدم موجودی هات ولت به مشکل خورده‌ایم.')
            ->view('mail.withdrawal.hotwallet-withdrawal-problem', [
                'currencyName' => $this->currencyName,
                'amount' => formatNumberTrimZeros($this->amount),
                'user' => $this->user,
                'baseUrl' => config('app.url')
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
