<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomVerifyEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $verficationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email - Task Management Api')
            ->greeting('Hello,' .$notifiable->name. '!')
            ->line('Thank you for using our task management application!')
            ->line('Please click the link below to verify your email')
            ->action('Verify Email Address', $verficationUrl);
    }


    protected function verificationUrl($notifiable): string
    {
        $url = config('app.url');
        
        return $url . '/verify-email?' . http_build_query([
            'id' => $notifiable->uuid, 
            'hash' => sha1($notifiable->getEmailForVerification()),
            'expires' => now()->addMinutes(60)->timestamp,
            'signature' => hash_hmac('sha256', 
                $notifiable->uuid . sha1($notifiable->getEmailForVerification()), 
                config('app.key')
            )
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
