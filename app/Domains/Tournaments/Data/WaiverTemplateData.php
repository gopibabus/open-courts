<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for updating a club's waiver template — the ordered, non-empty list of clauses.
 * Clauses may contain the {tournament} placeholder.
 */
final readonly class WaiverTemplateData
{
    /**
     * @param  array<int, string>  $clauses
     */
    public function __construct(
        public array $clauses,
    ) {}
}
