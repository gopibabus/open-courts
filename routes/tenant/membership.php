<?php

declare(strict_types=1);

use App\Http\Controllers\Membership\AcceptInvitationController;
use App\Http\Controllers\Membership\InvitationController;
use App\Http\Controllers\Membership\MemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Membership (club) Routes
|--------------------------------------------------------------------------
|
| Auto-loaded by routes/tenant.php INSIDE the tenant + subdomain group (so tenant()
| is resolved and spatie's team context is pinned to the club) but NOT inside 'auth'.
|
| - Admin routes (member directory, role changes, invitations) require 'auth' and are
|   further gated by the club-scoped 'member.manage' permission in their FormRequests.
| - Invitation ACCEPT routes are GUEST-accessible: the invitee may be logged out or have
|   no account yet, so they live OUTSIDE the auth group.
|
*/

Route::middleware('auth')->group(function () {
    // Member directory + role management.
    Route::get('members', [MemberController::class, 'index'])->name('membership.members.index');
    Route::patch('members/{member}', [MemberController::class, 'update'])->name('membership.members.update');

    // Invitations (list + send) — gated by member.manage in InviteMemberRequest.
    Route::get('invitations', [InvitationController::class, 'index'])->name('membership.invitations.index');
    Route::post('invitations', [InvitationController::class, 'store'])->name('membership.invitations.store');
});

// GUEST-accessible invitation acceptance (still tenant-scoped — runs on the club subdomain).
Route::get('invitations/{token}/accept', [AcceptInvitationController::class, 'create'])
    ->name('membership.invitations.accept');
Route::post('invitations/{token}/accept', [AcceptInvitationController::class, 'store'])
    ->name('membership.invitations.accept.store');
