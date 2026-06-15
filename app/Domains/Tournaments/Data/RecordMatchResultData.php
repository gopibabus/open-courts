<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for recording a completed singles match result.
 */
final readonly class RecordMatchResultData
{
    public function __construct(
        public int $tournamentId,
        public int $categoryId,
        public string $round,
        public int $playerOneId,
        public int $playerTwoId,
        public int $winnerId,
        public ?string $score,
    ) {}
}
