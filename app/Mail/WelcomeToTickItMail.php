<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeToTickItMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Workspace $workspace,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('jon@ticketcloser.online', 'Jon from tickIt'),
            subject: 'Welcome to tickIt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
