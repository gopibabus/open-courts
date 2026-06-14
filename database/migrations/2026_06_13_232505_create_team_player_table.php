<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team roster: which users play on which tournament team. Tenant-scoped via `tenant_id`.
 *
 * Because attach() writes pivot rows with a raw insert (bypassing model events), the
 * tenant_id must be supplied explicitly when attaching, e.g.:
 *   $team->players()->attach($userId, ['tenant_id' => tenant('id')]);
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_player', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_player');
    }
};
