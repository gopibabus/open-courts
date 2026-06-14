<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guardrail: the test suite must ALWAYS run against an isolated in-memory SQLite
 * database, never a real one. This is not optional — Pest's RefreshDatabase runs
 * `migrate:fresh`, so if the suite ever points at the live Postgres DB it silently
 * DROPS every table (this happened once when `php artisan test` was run inside the
 * app container, whose real DB_CONNECTION=pgsql env var shadowed phpunit.xml's
 * non-forced <env> values and wiped all tenants).
 *
 * phpunit.xml pins DB_CONNECTION=sqlite + DB_DATABASE=:memory: with force="true"
 * so these values win even when a real DB_* env var is present (e.g. in Docker).
 * If this test fails, STOP — running the rest of the suite could destroy live data.
 *
 * This test deliberately does NOT use RefreshDatabase: it must be safe to run even
 * if the (mis)configuration it guards against is in effect.
 */
class TestSuiteUsesIsolatedDatabaseTest extends TestCase
{
    public function test_default_connection_is_in_memory_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'), 'Tests must use the sqlite connection, not a real database.');
        $this->assertSame(':memory:', config('database.connections.sqlite.database'), 'The sqlite test database must be :memory:.');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName(), 'The active connection must be the in-memory database.');
    }
}
