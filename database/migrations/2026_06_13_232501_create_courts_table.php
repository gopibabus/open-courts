<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tennis courts owned by a club. Tenant-scoped (row-level) via `tenant_id`.
 * Thin on purpose — surface options, indoor/outdoor, lighting, pricing, etc.
 * will be fleshed out once the booking rules are defined.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('surface')->nullable(); // hard | clay | grass | carpet
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
