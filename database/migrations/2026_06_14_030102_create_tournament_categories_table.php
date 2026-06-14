<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories (events) within a tournament — e.g. "Men's Singles", "Mixed Doubles".
 * Tenant-scoped via `tenant_id`.
 *
 * `type` is backed by the App\Domains\Tournaments\Enums\CategoryType PHP enum
 * (singles | doubles | mixed) — a string column, NOT a DB enum (ADR-0001).
 * `max_entrants` is the optional capacity cap (null = unlimited).
 *
 * DRAW / BRACKET generation is OUT OF SCOPE for this slice; the schema leaves room for
 * a later slice to hang matches/draws off a category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_categories', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // singles | doubles | mixed
            $table->integer('max_entrants')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_categories');
    }
};
