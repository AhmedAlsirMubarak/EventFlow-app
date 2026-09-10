<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalAccessGrantedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $resetUrl) {}

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
        return (new MailMessage)
            ->subject('Your EventFlow stakeholder portal access')
            ->greeting('Welcome to EventFlow')
            ->line('You now have access to the stakeholder portal, where documents shared with your organization will appear.')
            ->action('Set Your Password', $this->resetUrl)
            ->line('This link will expire, so please set your password soon.');
    }
}
