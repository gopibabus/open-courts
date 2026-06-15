<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\RecordMatchResultData;
use App\Domains\Tournaments\Enums\MatchRound;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a recorded match result. The route is already gated by `can:tournament.manage`.
 * Checks the shape: the category belongs to THIS tournament + club, both players are club
 * members and distinct, and the winner is one of the two.
 */
class RecordMatchRequest extends FormRequest
{
    /** Route is already guarded by `can:tournament.manage`. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant()?->getTenantKey();
        $tournament = $this->route('tournament');
        $tournamentId = $tournament instanceof Tournament ? $tournament->getKey() : null;

        // Both players must be members of this club (bypasses model scopes, so tenant_id is explicit).
        $isClubMember = fn () => Rule::exists('tenant_user', 'user_id')->where('tenant_id', $tenantId);

        return [
            'category_id' => [
                'required',
                Rule::exists('tournament_categories', 'id')->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId),
            ],
            'round' => ['required', Rule::enum(MatchRound::class)],
            'player_one_id' => ['required', 'different:player_two_id', $isClubMember()],
            'player_two_id' => ['required', $isClubMember()],
            'winner_id' => ['required', 'in:'.$this->input('player_one_id').','.$this->input('player_two_id')],
            'score' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'player_one_id.different' => 'A match needs two different players.',
            'winner_id.in' => 'The winner must be one of the two players.',
        ];
    }

    public function toData(): RecordMatchResultData
    {
        /** @var Tournament $tournament */
        $tournament = $this->route('tournament');

        return new RecordMatchResultData(
            tournamentId: (int) $tournament->getKey(),
            categoryId: (int) $this->integer('category_id'),
            round: (string) $this->string('round'),
            playerOneId: (int) $this->integer('player_one_id'),
            playerTwoId: (int) $this->integer('player_two_id'),
            winnerId: (int) $this->integer('winner_id'),
            score: $this->filled('score') ? (string) $this->string('score') : null,
        );
    }
}
