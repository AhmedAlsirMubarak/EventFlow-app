<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting) {}

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
     *
     * Built entirely from the meeting itself, not $notifiable, so the same
     * notification works for internal Users and on-demand-routed external Contacts.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Meeting scheduled: '.$this->meeting->title)
            ->greeting('New meeting')
            ->line('You have been added to a meeting on **'.$this->meeting->event->name.'**.')
            ->line($this->meeting->title)
            ->line('When: '.$this->meeting->held_at->format('M j, Y \a\t g:i A'));

        if ($this->meeting->meeting_link) {
            $message->action('Join '.$this->meeting->provider.' Meeting', $this->meeting->meeting_link);
        } elseif ($this->meeting->location) {
            $message->line('Where: '.$this->meeting->location);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Meeting scheduled: '.$this->meeting->title,
            'body' => $this->meeting->held_at->format('M j, Y \a\t g:i A'),
            'url' => route('events.show', ['event' => $this->meeting->event_id, 'tab' => 'meetings']),
        ];
    }
}
