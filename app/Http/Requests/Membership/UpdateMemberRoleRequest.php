<?php

declare(strict_types=1);

namespace App\Http\Requests\Membership;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Change a member's role within the current club. Gated by the club-scoped
 * `member.manage` permission.
 */
class UpdateMemberRoleRequest extends FormRequest
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
