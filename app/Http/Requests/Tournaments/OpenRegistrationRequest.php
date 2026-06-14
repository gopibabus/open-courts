<?php

declare(strict_types=1);

namespace App\Http\Requests\Tournaments;

use App\Domains\Tournaments\Data\OpenRegistrationData;
use Illuminate\Foundation\Http\FormRequest;

class OpenRegistrationRequest extends FormRequest
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
            'registration_opens_on' => ['required', 'date'],
            'registration_closes_on' => ['nullable', 'date', 'after_or_equal:registration_opens_on'],
        ];
    }

    public function toData(): OpenRegistrationData
    {
        return new OpenRegistrationData(
            opensOn: (string) $this->string('registration_opens_on'),
            closesOn: $this->input('registration_closes_on'),
        );
    }
}
