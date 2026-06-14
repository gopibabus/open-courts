<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-off blackout periods when a court (or the whole club) is unavailable —
 * maintenance, private events, holidays, etc. Tenant-scoped via `tenant_id`.
 *
 * `court_id` is nullable: a null value means the blackout applies to the WHOLE club
 * (every court). A non-null value scopes the blackout to a single court.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_blackouts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('court_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['court_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_blackouts');
    }
};
