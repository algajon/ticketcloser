<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\SupportCase;

class NewSupportCaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportCase $supportCase)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $case = $this->supportCase;
        $url = route('app.tickets.show', $case->id);
        
        return (new MailMessage)
            ->subject("New Ticket: [{$case->case_number}] {$case->title}")
            ->greeting("Hello,")
            ->line("A new support ticket was just created via phone support.")
            ->line("**Ticket Number:** {$case->case_number}")
            ->line("**Title:** {$case->title}")
            ->line("**Priority:** " . ucfirst($case->priority))
            ->line("**Description:**")
            ->line($case->description)
            ->action('View Ticket', $url)
            ->line('tickIt AI Assistant');
    }
}
