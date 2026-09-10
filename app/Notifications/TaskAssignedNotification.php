<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

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
        $message = (new MailMessage)
            ->subject('You have been assigned a task: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name)
            ->line('You have been assigned a task on **'.$this->task->event->name.'**.')
            ->line($this->task->title);

        if ($this->task->due_date) {
            $message->line('Due: '.$this->task->due_date->format('M j, Y'));
        }

        return $message->action(
            'View Task',
            route('events.show', ['event' => $this->task->event_id, 'tab' => 'tasks']),
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
            'title' => 'Task assigned: '.$this->task->title,
            'body' => $this->task->event->name,
            'url' => route('events.show', ['event' => $this->task->event_id, 'tab' => 'tasks']),
        ];
    }
}
