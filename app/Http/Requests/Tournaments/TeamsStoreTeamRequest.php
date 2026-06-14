<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

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
        // The tournament comes from the route ({tournament}); only the name is in the body.
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
