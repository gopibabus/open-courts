<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An entrant's registration into a tournament category. Tenant-scoped via `tenant_id`.
 *
 *   - `user_id`    : the club member who registered (the primary entrant).
 *   - `partner_id` : the doubles/mixed partner (nullable; null for singles).
 *   - `seed`       : optional seeding number. SEEDING/DRAW generation is OUT OF SCOPE for
 *                    this slice — the column simply reserves room for a later slice.
 *   - `status`     : pending | confirmed | withdrawn (App\Domains\Tournaments\Enums\RegistrationStatus).
 *
 * Unique(category_id, user_id) prevents the same member registering twice for one category.
 *
 * NOTE: this migration must run AFTER create_tournament_categories (FK to
 * tournament_categories). The 0301xx prefix ordering guarantees that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('tournament_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('seed')->nullable();
            $table->string('status')->default('pending'); // pending | confirmed | withdrawn
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['category_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
