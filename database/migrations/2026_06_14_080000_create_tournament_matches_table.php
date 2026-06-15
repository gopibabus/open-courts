<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A match within a tournament category. Tenant-scoped (row-level) via `tenant_id`. Doubles
 * as both a recorded ad-hoc result AND a node in a generated single-elimination bracket:
 *
 * - `round` + `position` place the match in the bracket; `next_match_id` + `next_slot` are
 *   where the winner advances. Standalone (non-bracket) results leave those null.
 * - Players + winner are NULLABLE: a future-round bracket match has TBD players until the
 *   prior round is decided. `status` is `scheduled` until a result is recorded (`completed`).
 *
 * It's the source data a player's competitive record + trophies are derived from (completed
 * matches only). Singles-focused for now; doubles/team results are a later extension.
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
            $table->unsignedInteger('position')->nullable();            // 0-based slot within the round (bracket)
            $table->foreignId('next_match_id')->nullable()->constrained('tournament_matches')->nullOnDelete();
            $table->unsignedTinyInteger('next_slot')->nullable();       // which player slot (1|2) the winner advances into
            $table->foreignId('player_one_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('player_two_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('score')->nullable();                        // free-form, e.g. "6-4 6-2"
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled');             // scheduled | completed
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
