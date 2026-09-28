<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * One morning message per person covering every task that just reached a
 * deadline milestone — rather than a separate email per task, which turns
 * into noise the moment someone has several.
 */
class TaskDeadlineDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Task>  $overdue
     * @param  Collection<int, Task>  $dueToday
     * @param  Collection<int, Task>  $dueTomorrow
     * @param  int  $otherOverdueCount  overdue tasks already alerted on earlier, mentioned as a count only
     */
    public function __construct(
        public readonly Collection $overdue,
        public readonly Collection $dueToday,
        public readonly Collection $dueTomorrow,
        public readonly int $otherOverdueCount,
        public readonly string $today,
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
        $message = (new MailMessage)
            ->subject($this->headline())
            ->greeting('Good morning '.$notifiable->name);

        if ($this->overdue->isNotEmpty()) {
            $message->line('**Overdue**');
            $this->overdue->each(fn (Task $task) => $message->line(
                "• {$task->title} — {$task->event->name} (".$this->daysLate($task).')'
            ));
        }

        if ($this->dueToday->isNotEmpty()) {
            $message->line('**Due today**');
            $this->dueToday->each(fn (Task $task) => $message->line("• {$task->title} — {$task->event->name}"));
        }

        if ($this->dueTomorrow->isNotEmpty()) {
            $message->line('**Due tomorrow**');
            $this->dueTomorrow->each(fn (Task $task) => $message->line("• {$task->title} — {$task->event->name}"));
        }

        if ($this->otherOverdueCount > 0) {
            $message->line("You also have {$this->otherOverdueCount} other overdue ".str('task')->plural($this->otherOverdueCount).'.');
        }

        return $message->action('Open My Work', route('my-work.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $titles = $this->overdue->concat($this->dueToday)->concat($this->dueTomorrow)->pluck('title');

        return [
            'title' => $this->headline(),
            'body' => $titles->take(3)->implode(', ').($titles->count() > 3 ? ' and '.($titles->count() - 3).' more' : ''),
            'url' => route('my-work.index'),
        ];
    }

    private function headline(): string
    {
        $parts = collect([
            $this->overdue->count() ? $this->overdue->count().' overdue' : null,
            $this->dueToday->count() ? $this->dueToday->count().' due today' : null,
            $this->dueTomorrow->count() ? $this->dueTomorrow->count().' due tomorrow' : null,
        ])->filter();

        return 'Task deadlines: '.$parts->implode(', ');
    }

    private function daysLate(Task $task): string
    {
        $days = (int) Carbon::parse($task->due_date->toDateString())->diffInDays(Carbon::parse($this->today));

        return $days === 1 ? '1 day late' : "{$days} days late";
    }
}
