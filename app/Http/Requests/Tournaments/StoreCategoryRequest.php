<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\AddCategoryData;
use App\Domains\Tournaments\Enums\CategoryType;
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
            'max_entrants' => ['nullable', 'integer', 'min:2', 'max:1024'],
        ];
    }

    public function toData(): AddCategoryData
    {
        return new AddCategoryData(
            name: (string) $this->string('name'),
            type: CategoryType::from((string) $this->string('type')),
            maxEntrants: $this->filled('max_entrants') ? (int) $this->integer('max_entrants') : null,
        );
    }
}
