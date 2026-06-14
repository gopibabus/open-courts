<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input for adding a club member to a tournament's management (EC).
 *
 * The route is already guarded by `can:tournament.manage`; we re-assert here. The
 * club-membership rule lives in AddManagerToTournament and surfaces as a 422 via
 * ManagementException.
 */
class StoreTournamentManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tournament.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
