<?php

namespace App\Notifications;

use App\Models\StakeholderRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StakeholderRequestAssignedNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been assigned a request: '.$this->request->title)
            ->greeting('Hello '.$notifiable->name)
            ->line('You have been assigned a stakeholder request on **'.$this->request->event->name.'**.')
            ->line($this->request->title)
            ->action(
                'View Request',
                route('events.show', ['event' => $this->request->event_id, 'tab' => 'requests']),
            );
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Request assigned: '.$this->request->title,
            'body' => $this->request->event->name,
            'url' => route('events.show', ['event' => $this->request->event_id, 'tab' => 'requests']),
        ];
    }
}
