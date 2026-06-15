<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A recorded match result within a tournament category — who beat whom, in which round.
 * Tenant-scoped (row-level) via `tenant_id`. This is the source data a player's
 * competitive record + trophies are derived from. Singles-focused for now: two players
 * (users) and a winner; doubles/team results are a later extension.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('tournament_categories')->cascadeOnDelete();
            $table->string('round');                                    // final | semi_final | quarter_final | round_of_16 | group | other
            $table->foreignId('player_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player_two_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('winner_id')->constrained('users')->cascadeOnDelete(); // always one of the two
            $table->string('score')->nullable();                        // free-form, e.g. "6-4 6-2"
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tournament_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
