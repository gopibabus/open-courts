<?php

declare(strict_types=1);

namespace App\Domains\Membership\Mail;

use App\Domains\Membership\Models\Invitation;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Emails an invitee their tokenised "accept invitation" link on the club's subdomain.
 *
 * The accept URL is built from the club slug + the central domain + the invitation token,
 * so it works regardless of which origin the invite was created on.
 */
final class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly Tenant $club,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You're invited to join {$this->club->name} on OpenTennis");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invitation', with: [
            'clubName' => $this->club->name,
            'role' => $this->invitation->role,
            'acceptUrl' => $this->acceptUrl(),
        ]);
    }

    /**
     * Absolute URL of the accept-invitation page on the club's subdomain.
     */
    private function acceptUrl(): string
    {
        $host = $this->club->slug.'.'.config('tenancy.central_domain');

        return 'http://'.$host.'/invitations/'.$this->invitation->token.'/accept';
    }
}
