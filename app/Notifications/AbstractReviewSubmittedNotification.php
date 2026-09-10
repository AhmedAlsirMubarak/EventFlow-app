<?php

namespace App\Notifications;

use App\Models\AbstractReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractReviewSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly AbstractReview $review) {}

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
            ->subject('New review submitted: '.$this->review->submission->title)
            ->greeting('New review')
            ->line($this->review->reviewer->name.' reviewed **'.$this->review->submission->title.'**.')
            ->line('Score: '.$this->review->score.'/5 — Recommendation: '.$this->review->recommendation->value)
            ->action(
                'View Submission',
                route('events.show', ['event' => $this->review->submission->call->event_id, 'tab' => 'abstracts']),
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
            'title' => 'Review submitted: '.$this->review->submission->title,
            'body' => $this->review->reviewer->name.' — '.$this->review->recommendation->value,
            'url' => route('events.show', ['event' => $this->review->submission->call->event_id, 'tab' => 'abstracts']),
        ];
    }
}
