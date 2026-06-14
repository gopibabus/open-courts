<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Enums;

/**
 * The kind of category within a tournament. A string-backed PHP enum stored in the
 * `tournament_categories.type` column (DB-neutral — no DB enum, per ADR-0001).
 */
enum CategoryType: string
{
    case Singles = 'singles';
    case Doubles = 'doubles';
    case Mixed = 'mixed';

    /**
     * Whether this category is played in pairs (a partner is expected/allowed).
     */
    public function requiresPartner(): bool
    {
        return $this !== self::Singles;
    }
}
