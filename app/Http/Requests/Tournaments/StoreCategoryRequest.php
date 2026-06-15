<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\AddCategoryData;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\TournamentFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(CategoryType::class)],
            'format' => ['nullable', new Enum(TournamentFormat::class)],
            'is_team' => ['nullable', 'boolean'],
            'max_entrants' => ['nullable', 'integer', 'min:2', 'max:1024'],
        ];
    }

    public function toData(): AddCategoryData
    {
        return new AddCategoryData(
            name: (string) $this->string('name'),
            type: CategoryType::from((string) $this->string('type')),
            format: $this->filled('format')
                ? TournamentFormat::from((string) $this->string('format'))
                : TournamentFormat::SingleElimination,
            isTeam: $this->boolean('is_team'),
            maxEntrants: $this->filled('max_entrants') ? (int) $this->integer('max_entrants') : null,
        );
    }
}
