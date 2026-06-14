<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Data\RegisterClubData;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterClubRequest extends FormRequest
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
            'club_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'min:3', 'max:63',
                // DNS-safe subdomain label: lowercase alphanumerics + internal hyphens.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn($this->reservedSlugs()),
                Rule::unique(Tenant::class, 'slug'),
            ],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The subdomain may only contain lowercase letters, numbers, and hyphens.',
            'slug.not_in' => 'That subdomain is reserved.',
        ];
    }

    public function toData(): RegisterClubData
    {
        return new RegisterClubData(
            clubName: (string) $this->string('club_name'),
            slug: (string) $this->string('slug'),
            ownerName: (string) $this->string('owner_name'),
            ownerEmail: (string) $this->string('owner_email'),
            password: (string) $this->string('password'),
        );
    }

    /**
     * @return list<string>
     */
    private function reservedSlugs(): array
    {
        return ['www', 'app', 'admin', 'api', 'mail', 'static', 'assets', 'localhost'];
    }
}
