<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap — pin the isolated test environment BEFORE the framework boots
|--------------------------------------------------------------------------
|
| phpunit.xml's <env> values are not enough on their own: when the suite runs
| inside the app container, docker-compose exports real process env vars
| (DB_CONNECTION=pgsql, CACHE_STORE=redis, ...). PHP copies those into $_SERVER
| at startup, and Laravel's dotenv repository is IMMUTABLE and reads $_SERVER —
| so the live values shadow phpunit.xml and the suite would run against the real
| Postgres DB. RefreshDatabase's migrate:fresh then DROPS every table (this wiped
| all tenants once).
|
| Overwriting the superglobals here — before Laravel's LoadEnvironmentVariables
| bootstrapper runs — makes the test values win deterministically (immutable
| dotenv keeps whatever it sees first). The guardrail TestSuiteUsesIsolatedDatabaseTest
| asserts this actually took effect.
*/

require __DIR__.'/../vendor/autoload.php';

$forced = [
    'APP_ENV' => 'testing',
    'APP_URL' => 'http://localhost',
    'CENTRAL_DOMAIN' => 'localhost',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'MAIL_MAILER' => 'array',
];

foreach ($forced as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
