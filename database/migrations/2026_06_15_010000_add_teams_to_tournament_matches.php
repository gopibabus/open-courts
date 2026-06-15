<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team events: a match side can be a squad (team) instead of a player. A category flagged
 * `is_team` draws its participants from the tournament's teams; matches then use the
 * team_* columns (the player_* columns stay null). The frontend treats either as a generic
 * "side" with an id + name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->boolean('is_team')->default(false)->after('format');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->foreignId('team_one_id')->nullable()->after('player_two_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('team_two_id')->nullable()->after('team_one_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('winner_team_id')->nullable()->after('winner_id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_one_id');
            $table->dropConstrainedForeignId('team_two_id');
            $table->dropConstrainedForeignId('winner_team_id');
        });
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->dropColumn('is_team');
        });
    }
};
