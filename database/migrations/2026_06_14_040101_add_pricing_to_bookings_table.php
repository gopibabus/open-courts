<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds optional pricing to bookings. Money is stored as integer minor units
 * (price_cents) plus an ISO-4217 currency code — never a float (per ADR-0001 the
 * schema stays DB-neutral; integer + string are portable across SQLite/Postgres).
 *
 * The (court_id, starts_at, ends_at) composite index that backs the overlap query
 * already exists from the original create_bookings_table migration, so it is not
 * re-added here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('price_cents')->nullable()->after('status');
            $table->string('currency', 3)->nullable()->after('price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'currency']);
        });
    }
};
