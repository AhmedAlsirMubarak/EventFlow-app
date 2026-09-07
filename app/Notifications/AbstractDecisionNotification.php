<?php

namespace App\Notifications;

use App\Models\AbstractSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbstractDecisionNotification extends Notification
{
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
        $message = (new MailMessage)
            ->subject('A decision has been made on your submission: '.$this->submission->title)
            ->greeting('Hello '.$this->submission->speaker_name)
            ->line('There is an update on your submission **'.$this->submission->title.'** ('.$this->submission->reference.').')
            ->line('Status: **'.$this->submission->status->value.'**');

        if ($this->submission->decision_notes) {
            $message->line($this->submission->decision_notes);
        }

        return $message->action('View Submission Status', route('public.abstracts.status', $this->submission->public_token));
    }
}
