<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractSubmittedNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We received your abstract submission: '.$this->submission->title)
            ->greeting('Thank you, '.$this->submission->speaker_name)
            ->line('Your submission **'.$this->submission->title.'** ('.$this->submission->reference.') has been received.')
            ->line('You can track its status at any time using the link below.')
            ->action('View Submission Status', route('public.abstracts.status', $this->submission->public_token))
            ->line('Please keep this link — it is the only way to check your submission status.');
    }
}
