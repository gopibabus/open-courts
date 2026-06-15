<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an update to an existing (bracket) match: an optional winner (must be one of the
 * match's two players), a score and notes. Route is gated by `can:tournament.manage`.
 */
class UpdateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $match = $this->route('match');
        // The winner is one of the match's participants — players (singles/doubles) or teams.
        $participants = $match instanceof TournamentMatch
            ? array_values(array_filter([$match->player_one_id, $match->player_two_id, $match->team_one_id, $match->team_two_id]))
            : [];

        return [
            'winner_id' => ['nullable', Rule::in($participants)],
            'score' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'winner_id.in' => 'The winner must be one of the two players in this match.',
        ];
    }
}
