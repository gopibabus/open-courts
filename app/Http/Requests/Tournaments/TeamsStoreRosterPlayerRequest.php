<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input for adding a player to a team's roster. Named with the `Teams` prefix to
 * avoid clashing with the existing tournament FormRequests in this namespace.
 *
 * The route is already guarded by `can:team.manage`; we re-assert here for clarity. The
 * club-membership check (a deeper domain rule) lives in AddPlayerToTeam and surfaces as a
 * 422 via RosterException.
 */
class TeamsStoreRosterPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('team.manage') ?? false;
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
