<?php

declare(strict_types=1);

namespace App\Http\Requests\Facilities;

use App\Domains\Facilities\Data\CourtData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourtRequest extends FormRequest
{
    /** Route is already guarded by `can:court.manage`. */
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
            'name' => ['required', 'string', 'max:255'],
            'surface' => ['nullable', Rule::in(['hard', 'clay', 'grass', 'carpet'])],
            'is_active' => ['boolean'],
        ];
    }

    public function toData(): CourtData
    {
        return new CourtData(
            name: (string) $this->string('name'),
            surface: $this->filled('surface') ? (string) $this->string('surface') : null,
            isActive: $this->boolean('is_active'),
        );
    }
}
