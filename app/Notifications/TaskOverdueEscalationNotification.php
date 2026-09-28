<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, to whoever handed the task out, when it's been overdue for
 * SendTaskDeadlineAlerts::ESCALATE_AFTER_DAYS — so late work doesn't sit
 * unnoticed with only the assignee aware of it.
 */
class TaskOverdueEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly int $daysOverdue,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $assignee = $this->task->assignee?->name ?? 'its assignee';

        return (new MailMessage)
            ->subject("Overdue {$this->daysOverdue} days: {$this->task->title}")
            ->greeting('Heads up')
            ->line("**{$this->task->title}** on **{$this->task->event->name}** was due {$this->task->due_date->format('M j, Y')} and is still open.")
            ->line("It's assigned to {$assignee}, currently marked \"{$this->task->status->value}\".")
            ->action('View Task', route('events.show', ['event' => $this->task->event_id, 'tab' => 'tasks']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Overdue {$this->daysOverdue} days: {$this->task->title}",
            'body' => ($this->task->assignee?->name ?? 'Unassigned').' · '.$this->task->event->name,
            'url' => route('events.show', ['event' => $this->task->event_id, 'tab' => 'tasks']),
        ];
    }
}
