<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $url;

    public function __construct($url){
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->url;

        return (new MailMessage)
            ->subject('Verify Your Email Address - BetaEdge')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("Thank you for registering with BetaEdge.")
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address & Set Password', $verificationUrl)
            ->line('This verification link will expire in 24 hours.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, The BetaEdge Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
