<?php

namespace App\Console\Commands;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Notifications\ReminderDueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-due-reminders')]
#[Description('Email assigned staff for reminders that are now due and have not yet been notified')]
class SendDueReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $reminders = Reminder::query()
            ->where('status', ReminderStatus::Pending)
            ->whereNull('notified_at')
            ->where('remind_at', '<=', now())
            ->with('assignee', 'creator')
            ->get();

        foreach ($reminders as $reminder) {
            $recipient = $reminder->assignee ?? $reminder->creator;

            if ($recipient) {
                $recipient->notify(new ReminderDueNotification($reminder));
            }

            $reminder->update(['notified_at' => now()]);
        }

        $this->info("Notified {$reminders->count()} due reminder(s).");
    }
}
