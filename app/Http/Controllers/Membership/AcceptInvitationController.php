<?php

declare(strict_types=1);

namespace App\Http\Controllers\Membership;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\AcceptInvitation;
use App\Domains\Membership\Exceptions\InvitationNotAcceptable;
use App\Domains\Membership\Models\Invitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\AcceptInvitationRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Public (guest-accessible) acceptance of a club invitation. Runs inside the tenant +
 * subdomain group but OUTSIDE auth, so an invitee who is logged out (or has no account)
 * can still reach it. The invitation is resolved from its {token}.
 */
class AcceptInvitationController extends Controller
{
    /**
     * GET — show the accept form for a valid, pending invitation token.
     */
    public function create(string $token): Response
    {
        $invitation = $this->pendingOrAbort($token);

        $needsAccount = ! User::query()->where('email', $invitation->email)->exists();

        return Inertia::render('membership/invitations/accept', [
            'club' => [
                'name' => tenant()->name,
                'slug' => tenant()->slug,
            ],
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'token' => $invitation->token,
            ],
            // When false, the invitee already has an account — just confirm to join.
            'needsAccount' => $needsAccount,
        ]);
    }

    /**
     * POST — accept the invitation, provisioning/attaching the user, then sign them in and
     * bounce them to the club dashboard.
     */
    public function store(AcceptInvitationRequest $request, string $token, AcceptInvitation $acceptInvitation): HttpResponse
    {
        $invitation = $this->pendingOrAbort($token);

        try {
            $user = $acceptInvitation->handle(
                invitation: $invitation,
                name: $request->filled('name') ? (string) $request->string('name') : null,
                password: $request->filled('password') ? (string) $request->string('password') : null,
            );
        } catch (InvitationNotAcceptable) {
            abort(HttpResponse::HTTP_GONE);
        }

        Auth::login($user);

        // The dashboard lives on this same club subdomain — a normal redirect is fine, but
        // we use Inertia::location for a clean full-page visit after a cross-context flow.
        return Inertia::location($this->clubDashboardUrl($request));
    }

    /**
     * Resolve a pending invitation by token or abort (404 for unknown, 410 for stale).
     */
    private function pendingOrAbort(string $token): Invitation
    {
        $invitation = Invitation::query()->where('token', $token)->first();

        abort_if($invitation === null, HttpResponse::HTTP_NOT_FOUND);
        abort_unless($invitation->isPending(), HttpResponse::HTTP_GONE, 'This invitation is no longer valid.');

        return $invitation;
    }

    /**
     * Absolute URL of the current club's dashboard, preserving scheme + non-default port.
     */
    private function clubDashboardUrl(AcceptInvitationRequest $request): string
    {
        $port = $request->getPort();
        $suffix = in_array($port, [80, 443, null], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.tenant()->slug.'.'.config('tenancy.central_domain').$suffix.'/';
    }
}
