<?php

declare(strict_types=1);

namespace App\Http\Requests\Facilities;

use App\Domains\Facilities\Data\BlackoutData;
use App\Domains\Facilities\Models\Court;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourtBlackoutRequest extends FormRequest
{
    /** Route is already guarded by `can:court.manage`. */
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
            // null => whole-club blackout; otherwise must be a court in THIS club.
            // Rule::exists builds a raw query that bypasses the model's global scope,
            // so the tenant_id is constrained explicitly to keep this tenant-safe.
            'court_id' => [
                'nullable',
                Rule::exists(Court::class, 'id')->where('tenant_id', tenant()?->getTenantKey()),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): BlackoutData
    {
        return new BlackoutData(
            courtId: $this->filled('court_id') ? (int) $this->integer('court_id') : null,
            startsAt: (string) $this->string('starts_at'),
            endsAt: (string) $this->string('ends_at'),
            reason: $this->filled('reason') ? (string) $this->string('reason') : null,
        );
    }
}
