<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Models\TournamentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a manual seeding order — a list of registration ids that must all belong to the
 * category being seeded. Route is gated by `can:tournament.manage`.
 */
class StoreSeedingRequest extends FormRequest
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
        $category = $this->route('category');
        $categoryId = $category instanceof TournamentCategory ? $category->getKey() : null;

        return [
            'entrants' => ['required', 'array', 'min:1'],
            'entrants.*' => ['integer', Rule::exists('registrations', 'id')->where('category_id', $categoryId)],
        ];
    }
}
