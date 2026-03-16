<?php

namespace App\Mail;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Team $team,
        public string $inviterName,
        public string $inviteeEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join team: {$this->team->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invitation',
        );
    }
}
