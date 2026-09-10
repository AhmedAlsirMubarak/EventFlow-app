<?php

namespace App\Notifications;

use App\Models\StakeholderRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StakeholderRequestStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly StakeholderRequest $request) {}

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
            ->subject('Update on your request: '.$this->request->title)
            ->greeting('Hello '.$this->request->contact->full_name)
            ->line('There is an update on your request **'.$this->request->title.'**.')
            ->line('Status: **'.$this->request->status->value.'**')
            ->action('View Request', route('portal.requests.show', $this->request));
    }
}
