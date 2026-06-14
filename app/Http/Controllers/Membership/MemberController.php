<?php

declare(strict_types=1);

namespace App\Http\Controllers\Membership;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\AssignMemberRole;
use App\Domains\Membership\Models\Invitation;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\UpdateMemberRoleRequest;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

/**
 * Club member directory + role management. The spatie team context is already pinned to
 * the current club by the tenancy middleware. Both the listing of roles and the role
 * change are club-scoped.
 */
class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $club = tenant();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($club->getTenantKey());

        $members = $club->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(function (User $user) {
                $user->unsetRelation('roles');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->all(),
                ];
            })
            ->values();

        $pendingInvitations = Invitation::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get(['id', 'email', 'role', 'expires_at', 'created_at'])
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at->toIso8601String(),
                'created_at' => $invitation->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('membership/members/index', [
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'roles' => array_keys(RolePermissionSeeder::roleMatrix()),
            'can' => [
                'manageMembers' => $request->user()?->can('member.manage') ?? false,
            ],
        ]);
    }

    public function update(UpdateMemberRoleRequest $request, User $member, AssignMemberRole $assignMemberRole): RedirectResponse
    {
        /** @var Tenant $club */
        $club = tenant();

        abort_unless($club->users()->whereKey($member->id)->exists(), 404);

        $assignMemberRole->handle($club, $member, (string) $request->string('role'));

        return back();
    }
}
