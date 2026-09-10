<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentPendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Document $document) {}

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
            ->subject('Document awaiting your approval: '.$this->document->title)
            ->greeting('Approval requested')
            ->line('A document in **'.$this->document->event->name.'** needs your review before it can be shared.')
            ->line($this->document->title)
            ->action('Review Document', route('events.show', ['event' => $this->document->event_id, 'tab' => 'documents']));
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Approval requested: '.$this->document->title,
            'body' => $this->document->event->name,
            'url' => route('events.show', ['event' => $this->document->event_id, 'tab' => 'documents']),
        ];
    }
}
