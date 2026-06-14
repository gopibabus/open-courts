<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tournament teams. Tenant-scoped via `tenant_id`.
 *
 * NOTE: this is the *domain* concept of a team (a squad competing in a tournament),
 * which is distinct from spatie/laravel-permission's "teams" feature — the latter
 * only reuses the `tenant_id` column to scope roles and has no table of its own.
 *
 * `tournament_id` is nullable so a team can exist before being entered into a draw.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
