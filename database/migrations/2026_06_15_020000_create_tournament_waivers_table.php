<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A player's signed liability waiver for a tournament. Tenant-scoped. One waiver per
 * (tournament, player); `signature` is the typed full name and `signed_at` the timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_waivers', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('signature');
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_waivers');
    }
};
