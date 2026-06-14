<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Mail;

use App\Domains\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation that a court booking is reserved. The court relation is eager-loaded
 * by the listener before queueing so the view never lazy-loads off a serialized model.
 */
final class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}

    public function envelope(): Envelope
    {
        $courtName = $this->booking->court?->name ?? 'your court';

        return new Envelope(subject: "Booking confirmed — {$courtName}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.booking-confirmation', with: [
            'courtName' => $this->booking->court?->name ?? 'Court',
            'startsAt' => $this->booking->starts_at?->format('D j M Y, H:i'),
            'endsAt' => $this->booking->ends_at?->format('H:i'),
        ]);
    }
}
