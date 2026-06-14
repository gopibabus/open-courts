<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Mail;

use App\Domains\Support\Models\SupportRequest;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the support inbox that a club member raised a help-desk request. The member's
 * email is set as Reply-To so support can respond to them directly. Self-sufficient (it
 * resolves the club + submitter from the model) so it survives running on a queue worker
 * outside the originating tenancy context.
 */
final class SupportRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SupportRequest $supportRequest) {}

    public function envelope(): Envelope
    {
        $this->supportRequest->loadMissing('user');
        $club = Tenant::find($this->supportRequest->tenant_id);

        return new Envelope(
            subject: '[Support · '.($club?->name ?? 'Club').'] '.$this->supportRequest->subject,
            replyTo: $this->supportRequest->user
                ? [new Address($this->supportRequest->user->email, $this->supportRequest->user->name)]
                : [],
        );
    }

    public function content(): Content
    {
        $this->supportRequest->loadMissing('user');
        $club = Tenant::find($this->supportRequest->tenant_id);

        return new Content(view: 'mail.support-request', with: [
            'clubName' => $club?->name ?? 'Club',
            'memberName' => $this->supportRequest->user?->name ?? 'A member',
            'memberEmail' => $this->supportRequest->user?->email,
            'category' => $this->supportRequest->category,
            'subject' => $this->supportRequest->subject,
            'body' => $this->supportRequest->message,
        ]);
    }
}
