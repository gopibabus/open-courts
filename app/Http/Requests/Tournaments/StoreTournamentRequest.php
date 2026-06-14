<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\CreateTournamentData;
use App\Domains\Tournaments\Enums\TournamentFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTournamentRequest extends FormRequest
{
    /**
     * Route is already guarded by `can:tournament.manage`; authorize here too for clarity.
     */
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
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', new Enum(TournamentFormat::class)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function toData(): CreateTournamentData
    {
        return new CreateTournamentData(
            name: (string) $this->string('name'),
            format: TournamentFormat::from((string) $this->string('format')),
            startsOn: $this->input('starts_on'),
            endsOn: $this->input('ends_on'),
        );
    }
}
