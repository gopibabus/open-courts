<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\CreateTeamData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input for creating a team. Named with the `Teams` prefix to avoid clashing
 * with the existing tournament FormRequests in this namespace.
 *
 * The route is already guarded by `can:team.manage`; we re-assert here for clarity.
 */
class TeamsStoreTeamRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            // Optional: a team may be tied to a tournament. Scoped to the current club.
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
        ];
    }

    public function toData(): CreateTeamData
    {
        return new CreateTeamData(
            name: (string) $this->string('name'),
            tournamentId: $this->filled('tournament_id') ? (int) $this->integer('tournament_id') : null,
        );
    }
}
