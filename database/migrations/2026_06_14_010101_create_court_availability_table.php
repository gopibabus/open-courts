<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A court's recurring weekly availability windows. Tenant-scoped via `tenant_id`.
 *
 * One row = "on this day of week, the court is open from opens_at to closes_at".
 * A court may have several rows per day (split shifts). `day_of_week` is 0=Mon..6=Sun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_availability', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('day_of_week'); // 0=Mon .. 6=Sun
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['court_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_availability');
    }
};
