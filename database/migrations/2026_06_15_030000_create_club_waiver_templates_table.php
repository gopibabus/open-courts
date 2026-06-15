<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A club's editable waiver template — the ordered list of clauses a player agrees to before
 * competing. Tenant-scoped, at most one row per club (clubs without a row fall back to the
 * platform default clauses). `clauses` is a JSON array of strings; each may contain the
 * {tournament} placeholder, substituted with the tournament name when shown / signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_waiver_templates', function (Blueprint $table) {
            $table->id();
            // No separate ->index(): the unique('tenant_id') below already creates a backing
            // index that serves the BelongsToTenant scope's WHERE (one template row per club).
            $table->string('tenant_id');
            $table->json('clauses');
            $table->timestamps();

            $table->unique('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_waiver_templates');
    }
};
