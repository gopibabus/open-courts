<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-level super-admin flag. This is deliberately separate from spatie roles:
 * club roles are scoped per-tenant, whereas a platform admin operates across every
 * club. A boolean flag + a Gate::before hook (see AppServiceProvider) is the clean way
 * to express "this user bypasses all authorization checks".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });
    }
};
