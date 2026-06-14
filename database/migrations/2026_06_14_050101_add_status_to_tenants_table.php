<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle status for a club (tenant): `active` (default) or `suspended`.
 *
 * A suspended club cannot be entered (see App\Http\Middleware\EnsureClubActive),
 * but the row is retained so a platform operator can reactivate it later.
 *
 * This is a real column, so it is added to Tenant::getCustomColumns() (otherwise
 * stancl's VirtualColumn would fold it into the `data` JSON blob). Kept DB-neutral
 * (a plain string with an app-level default) so it is portable to Postgres/MySQL/SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status')->default('active')->index()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
