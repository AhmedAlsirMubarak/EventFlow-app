<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractAwaitingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly AbstractSubmission $submission) {}

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
            ->subject('New abstract submission to review: '.$this->submission->title)
            ->greeting('New submission')
            ->line('A new abstract was submitted to **'.$this->submission->call->event->name.'** and is awaiting your review.')
            ->line($this->submission->title)
            ->action('Review Submission', route('events.show', ['event' => $this->submission->call->event_id, 'tab' => 'abstracts']));
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New submission: '.$this->submission->title,
            'body' => $this->submission->call->event->name,
            'url' => route('events.show', ['event' => $this->submission->call->event_id, 'tab' => 'abstracts']),
        ];
    }
}
