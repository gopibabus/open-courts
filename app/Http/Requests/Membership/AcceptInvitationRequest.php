<?php

declare(strict_types=1);

namespace App\Http\Requests\Membership;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Accept an invitation. GUEST-accessible — the invitee may be logged out (or have no
 * account at all). When the invited email has no account yet, a name + password are
 * required to provision one; for an existing account they are ignored.
 *
 * The invitation is resolved from the {token} route parameter; validity (pending/expired)
 * is checked in the controller / AcceptInvitation action, not here.
 */
class AcceptInvitationRequest extends FormRequest
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
        if ($this->emailHasAccount()) {
            return [
                'name' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Whether the invited email already belongs to a user account.
     */
    private function emailHasAccount(): bool
    {
        $invitation = $this->invitation();

        if ($invitation === null) {
            return false;
        }

        return User::query()->where('email', $invitation->email)->exists();
    }

    /**
     * The invitation resolved from the {token} route parameter (tenant-scoped).
     */
    public function invitation(): ?Invitation
    {
        $token = (string) $this->route('token');

        return Invitation::query()->where('token', $token)->first();
    }
}
