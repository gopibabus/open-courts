<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Club invitations. A club admin invites someone by email + role; the invitee follows
 * the tokenised link and joins the club. Tenant-scoped (row-level) via `tenant_id`.
 *
 * One pending invite per (club, email) — enforced by the composite unique index. The
 * `token` is a random, unguessable secret used to look up the invite from the accept link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('email');
            $table->string('role');
            $table->string('token')->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('expires_at');
            $table->dateTime('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
