<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSharedNotification extends Notification
{
    public function __construct(private readonly Document $document) {}

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
            ->subject('A document has been shared with you: '.$this->document->title)
            ->greeting('Hello '.$notifiable->full_name)
            ->line('A new document has been shared with you for '.$this->document->event->name.'.')
            ->line('Document: '.$this->document->title.' ('.$this->document->category->value.')');

        if ($this->document->acknowledgment_required) {
            $message->line('This document requires your acknowledgment once reviewed.');
        }

        return $message->action('View Document', route('portal.documents.show', $this->document));
    }
}
