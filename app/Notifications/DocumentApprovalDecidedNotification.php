<?php

namespace App\Notifications;

use App\Models\DocumentApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentApprovalDecidedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DocumentApproval $approval) {}

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
        $document = $this->approval->document;
        $decision = strtolower($this->approval->status->value);

        $message = (new MailMessage)
            ->subject('Document '.$decision.': '.$document->title)
            ->greeting('Approval decision')
            ->line('Your document **'.$document->title.'** was '.$decision.' in **'.$document->event->name.'**.');

        if ($this->approval->comments) {
            $message->line('Reviewer comments: '.$this->approval->comments);
        }

        return $message->action('View Document', route('events.show', ['event' => $document->event_id, 'tab' => 'documents']));
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $document = $this->approval->document;

        return [
            'title' => 'Document '.strtolower($this->approval->status->value).': '.$document->title,
            'body' => $document->event->name,
            'url' => route('events.show', ['event' => $document->event_id, 'tab' => 'documents']),
        ];
    }
}
