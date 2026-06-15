<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\SignWaiverData;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a player signing a tournament waiver: they must agree (consent box) and type
 * their full name as the signature. Any authenticated club member may sign their own waiver.
 */
class SignWaiverRequest extends FormRequest
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
        return [
            'agree' => ['accepted'],
            'signature' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'agree.accepted' => 'You must agree to the waiver to sign it.',
        ];
    }

    public function toData(): SignWaiverData
    {
        /** @var Tournament $tournament */
        $tournament = $this->route('tournament');

        return new SignWaiverData(
            tournamentId: (int) $tournament->getKey(),
            userId: (int) $this->user()->id,
            signature: (string) $this->string('signature'),
        );
    }
}
