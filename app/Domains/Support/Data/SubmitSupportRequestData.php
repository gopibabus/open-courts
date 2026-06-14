<?php

declare(strict_types=1);

namespace App\Domains\Support\Data;

/**
 * Input for a member submitting a help-desk request from the in-app Help page.
 */
final readonly class SubmitSupportRequestData
{
    public function __construct(
        public int $userId,
        public string $category,
        public string $subject,
        public string $message,
    ) {}
}
