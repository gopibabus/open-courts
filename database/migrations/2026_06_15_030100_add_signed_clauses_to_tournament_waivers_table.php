<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the exact clauses a player agreed to at signing time. Editing the club's waiver
 * template later never changes what a past signer agreed to — the record is what they saw.
 * Nullable so rows signed before this migration keep working (they show the live template).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_waivers', function (Blueprint $table) {
            $table->json('signed_clauses')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_waivers', function (Blueprint $table) {
            $table->dropColumn('signed_clauses');
        });
    }
};
