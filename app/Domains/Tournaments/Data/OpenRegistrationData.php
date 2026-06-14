<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for opening registration on a tournament: the registration window. Both are
 * ISO date strings (Y-m-d). `closesOn` may be null (open-ended).
 */
final readonly class OpenRegistrationData
{
    public function __construct(
        public string $opensOn,
        public ?string $closesOn,
    ) {}
}
