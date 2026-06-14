<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the existing `tournaments` table for the Tournaments management slice:
 *   - `format`: how the event is run (single_elimination | round_robin). Backed by the
 *     App\Domains\Tournaments\Enums\TournamentFormat PHP enum — NOT a DB enum, per
 *     ADR-0001 (DB-neutral). Default 'single_elimination'.
 *   - `registration_opens_on` / `registration_closes_on`: the registration window. Set
 *     when registration is opened; entrants may only register inside this window.
 *
 * Added as a separate migration so the original create-tournaments migration stays intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('format')->default('single_elimination')->after('status');
            $table->date('registration_opens_on')->nullable()->after('format');
            $table->date('registration_closes_on')->nullable()->after('registration_opens_on');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['format', 'registration_opens_on', 'registration_closes_on']);
        });
    }
};
