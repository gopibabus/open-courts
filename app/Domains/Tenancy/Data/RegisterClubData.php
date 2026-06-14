<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Data;

/**
 * Input for registering a new club (tenant) and its owner.
 */
final readonly class RegisterClubData
{
    public function __construct(
        public string $clubName,
        public string $slug,
        public string $ownerName,
        public string $ownerEmail,
        public string $password,
    ) {}
}
