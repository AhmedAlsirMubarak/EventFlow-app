<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Reminder $reminder) {}

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
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reminder due: '.$this->reminder->title,
            'body' => $this->reminder->event->name,
            'url' => route('events.show', ['event' => $this->reminder->event_id, 'tab' => 'reminders']),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Reminder: '.$this->reminder->title)
            ->greeting('Follow-up due')
            ->line('This reminder for **'.$this->reminder->event->name.'** is now due:')
            ->line($this->reminder->title);

        if ($this->reminder->notes) {
            $message->line($this->reminder->notes);
        }

        return $message->action('View Event', route('events.show', ['event' => $this->reminder->event_id, 'tab' => 'reminders']));
    }
}
