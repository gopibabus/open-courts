<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Support;

/**
 * The platform's default waiver clauses + the {tournament} placeholder substitution shared by
 * the waiver page and the signing action. A club that hasn't customised its template falls
 * back to {@see self::clauses()}; clauses may contain `{tournament}`, replaced with the
 * tournament's name when shown to or signed by a player.
 */
final class DefaultWaiver
{
    /**
     * The platform-default clause templates (raw, with the {tournament} placeholder intact).
     *
     * @return array<int, string>
     */
    public static function clauses(): array
    {
        return [
            'I am voluntarily entering {tournament} and understand that tennis carries inherent risks of injury.',
            'I confirm I am medically fit to compete and will stop and seek help if I feel unwell.',
            'I release the club, its organisers and volunteers from liability for any injury, loss or damage sustained while participating, to the extent permitted by law.',
            'I consent to photos or video taken at the event being used for club communications.',
        ];
    }

    /**
     * Substitute the {tournament} placeholder in each clause with the tournament's name.
     *
     * @param  array<int, string>  $clauses
     * @return array<int, string>
     */
    public static function resolve(array $clauses, string $tournamentName): array
    {
        return array_values(array_map(
            static fn (string $clause): string => str_replace('{tournament}', $tournamentName, $clause),
            $clauses,
        ));
    }
}
