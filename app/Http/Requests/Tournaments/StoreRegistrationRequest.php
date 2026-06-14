<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registering an entrant only requires being an authenticated club member — the `auth`
 * middleware already enforces that, so authorize() just confirms a user is present.
 *
 * `partner_id` (doubles/mixed) must reference a member of THIS club. We scope the
 * existence check to the current tenant's membership via the tenant_user pivot.
 */
class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'partner_id' => [
                'nullable',
                'integer',
                Rule::notIn([$this->user()?->getKey()]), // can't partner yourself
                Rule::exists('tenant_user', 'user_id')->where('tenant_id', tenant('id')),
            ],
        ];
    }
}
