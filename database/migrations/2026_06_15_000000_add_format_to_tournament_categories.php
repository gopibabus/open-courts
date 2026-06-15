<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A category's draw format — single elimination (bracket) or round robin (group + standings).
 * Per-category so one tournament can mix formats. Defaults to single elimination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->string('format')->default('single_elimination')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
