<?php

declare(strict_types=1);

namespace App\Http\Controllers\Membership;

use App\Domains\Membership\Actions\InviteMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\InviteMemberRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Manage a club's pending invitations. Both actions are gated by `member.manage` via
 * InviteMemberRequest::authorize(). Tenant-scoped: invitations belong to the active club.
 */
class InvitationController extends Controller
{
    /**
     * The pending-invitation list is rendered alongside the member directory, so this
     * endpoint just lands the admin there.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('membership.members.index');
    }

    public function store(InviteMemberRequest $request, InviteMember $inviteMember): RedirectResponse
    {
        $email = (string) $request->string('email');

        // A member of this club can't be (re)invited.
        $alreadyMember = tenant()->users()->where('email', $email)->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'That person is already a member of this club.',
            ]);
        }

        $inviteMember->handle(
            email: $email,
            role: (string) $request->string('role'),
            invitedBy: $request->user()?->id,
        );

        return back();
    }
}
