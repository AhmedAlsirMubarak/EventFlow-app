<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Idea;
use App\Models\StakeholderRequest;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Notifies internal staff (the commentable's creator/assignee) about a new comment
 * on an Idea, Task, or StakeholderRequest — including one posted by a stakeholder
 * through the portal, which staff previously had no way to learn about besides
 * manually re-checking the request.
 */
class CommentPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Comment $comment) {}

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
        $commentable = $this->comment->commentable;

        return (new MailMessage)
            ->subject('New comment on: '.$commentable->title)
            ->greeting('New comment')
            ->line($this->authorName().' commented on **'.$commentable->title.'** in **'.$commentable->event->name.'**.')
            ->line($this->comment->body)
            ->action('View', $this->url());
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $commentable = $this->comment->commentable;

        return [
            'title' => 'New comment on: '.$commentable->title,
            'body' => $this->authorName().': '.Str::limit($this->comment->body, 120),
            'url' => $this->url(),
        ];
    }

    private function authorName(): string
    {
        return $this->comment->user?->name ?? $this->comment->contact?->full_name ?? 'A stakeholder';
    }

    private function url(): string
    {
        $commentable = $this->comment->commentable;

        return route('events.show', [
            'event' => $commentable->event_id,
            'tab' => match ($commentable::class) {
                Idea::class => 'ideas',
                Task::class => 'tasks',
                StakeholderRequest::class => 'requests',
                default => 'ideas',
            },
        ]);
    }
}
