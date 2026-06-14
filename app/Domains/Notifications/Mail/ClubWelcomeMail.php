<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Mail;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ClubWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Tenant $club) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Welcome to OpenTennis — {$this->club->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.club-welcome', with: [
            'clubName' => $this->club->name,
            'clubUrl' => $this->club->slug.'.'.config('tenancy.central_domain'),
        ]);
    }
}
