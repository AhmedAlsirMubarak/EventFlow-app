<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentVersionUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly DocumentVersion $version,
    ) {}

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
            ->subject('Updated version available: '.$this->document->title)
            ->greeting('Hello '.$notifiable->full_name)
            ->line('A new version (v'.$this->version->version_number.') of "'.$this->document->title.'" is now available.')
            ->action('View Document', route('portal.documents.show', $this->document));
    }
}
