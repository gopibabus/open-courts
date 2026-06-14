<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Domains\Booking\Data\BookCourtData;
use App\Domains\Facilities\Models\Court;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a booking request. The route is already gated by `can:court.book`; this
 * checks the *shape* (times, court belongs to THIS club, ends_at > starts_at). The
 * domain rules — availability, blackout, overlap — live in the BookCourt action.
 */
class StoreBookingRequest extends FormRequest
{
    /** Route is already guarded by `can:court.book`. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Rule::exists bypasses the model's global tenant scope, so tenant_id is
            // constrained explicitly to keep cross-club booking impossible.
            'court_id' => [
                'required',
                Rule::exists(Court::class, 'id')->where('tenant_id', tenant()?->getTenantKey()),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function toData(): BookCourtData
    {
        return new BookCourtData(
            courtId: (int) $this->integer('court_id'),
            startsAt: (string) $this->string('starts_at'),
            endsAt: (string) $this->string('ends_at'),
        );
    }
}
