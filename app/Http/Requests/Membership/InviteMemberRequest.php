<?php

declare(strict_types=1);

namespace App\Http\Requests\Membership;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Invite a new member by email + role. Authorisation is gated by the club-scoped
 * `member.manage` permission (spatie team context is already pinned to the current club
 * by the tenancy middleware).
 */
class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('member.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedRoles(): array
    {
        return array_keys(RolePermissionSeeder::roleMatrix());
    }
}
