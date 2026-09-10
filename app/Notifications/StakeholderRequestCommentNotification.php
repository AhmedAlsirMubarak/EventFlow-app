<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StakeholderRequestCommentNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->comment->commentable;

        return (new MailMessage)
            ->subject('New reply on your request: '.$request->title)
            ->greeting('Hello '.$request->contact->full_name)
            ->line('There is a new reply on your request **'.$request->title.'**.')
            ->line($this->comment->body)
            ->action('View Request', route('portal.requests.show', $request));
    }
}
