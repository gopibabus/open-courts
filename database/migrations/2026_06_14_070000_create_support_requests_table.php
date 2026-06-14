<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Help-desk: a support request a club member submits from the in-app Help page.
 * Tenant-scoped (row-level) via `tenant_id` so each club only sees its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category');           // booking | courts | tournaments | membership | billing | other
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('open'); // open | closed (resolution is a later slice)
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
