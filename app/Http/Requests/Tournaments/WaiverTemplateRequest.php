<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\WaiverTemplateData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a club's waiver template edit. Authorisation is enforced at the route layer
 * (`can:tournament.manage`). Blank/whitespace-only clause rows are trimmed away before
 * validation, so an admin can leave an empty row in the editor without it being saved; at
 * least one real clause must remain.
 */
class WaiverTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clauses = collect($this->input('clauses', []))
            ->map(fn ($clause) => is_string($clause) ? trim($clause) : $clause)
            ->filter(fn ($clause) => is_string($clause) && $clause !== '')
            ->values()
            ->all();

        $this->merge(['clauses' => $clauses]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'clauses' => ['required', 'array', 'min:1'],
            'clauses.*' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clauses.required' => 'Add at least one waiver clause.',
            'clauses.min' => 'Add at least one waiver clause.',
        ];
    }

    public function toData(): WaiverTemplateData
    {
        /** @var array<int, string> $clauses */
        $clauses = array_values($this->validated()['clauses']);

        return new WaiverTemplateData(clauses: $clauses);
    }
}
