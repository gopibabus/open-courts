<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tournament management — the EC (executive committee). A tournament's management is
 * the set of club members who run THAT tournament; it can differ from tournament to
 * tournament. Tenant-scoped via `tenant_id`.
 *
 * Like the team roster, attach() bypasses model events, so tenant_id must be supplied
 * explicitly on the pivot when attaching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_management', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tournament_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_management');
    }
};
