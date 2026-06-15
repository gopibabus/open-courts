<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Enums;

/**
 * The round a match was played in. String-backed (stored in `tournament_matches.round`).
 * Drives the trophy derivation: winning a `Final` is a title, losing it is runner-up, and
 * playing a `SemiFinal` (without reaching the final) is a semi-finalist placement.
 */
enum MatchRound: string
{
    case Final = 'final';
    case SemiFinal = 'semi_final';
    case QuarterFinal = 'quarter_final';
    case RoundOf16 = 'round_of_16';
    case Group = 'group';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Final => 'Final',
            self::SemiFinal => 'Semi-final',
            self::QuarterFinal => 'Quarter-final',
            self::RoundOf16 => 'Round of 16',
            self::Group => 'Group stage',
            self::Other => 'Match',
        };
    }
}
